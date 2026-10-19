"""Pruebas HTTP de lectura y autorización, sin modificar registros Northwind."""
import argparse,urllib.request,urllib.parse,urllib.error,http.cookiejar,re,json,pathlib
p=argparse.ArgumentParser();p.add_argument('--url',default='http://127.0.0.1:8089/index.php');args=p.parse_args()
checks=[]
class Client:
 def __init__(self):self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def req(self,route,data=None,params=None):
  url=args.url+'?'+urllib.parse.urlencode({'r':route,**(params or {})})
  request=urllib.request.Request(url,data=None if data is None else urllib.parse.urlencode(data).encode())
  try:
   with self.opener.open(request,timeout=20) as r:return r.status,r.read().decode('utf-8-sig'),dict(r.headers)
  except urllib.error.HTTPError as e:return e.code,e.read().decode('utf-8-sig'),dict(e.headers)
 def token(self,html):return re.search(r'name="_csrf" value="([a-f0-9]+)"',html).group(1)
def check(name,condition):
 checks.append({'name':name,'passed':bool(condition)});print(('PASS ' if condition else 'FAIL ')+name)

c=Client();status,body,h=c.req('catalog',params={'module':'clientes'});check('Ruta privada requiere acceso',status==200 and 'Iniciar sesión' in body)
check('CSP local presente',"default-src 'self'" in h.get('Content-Security-Policy',''))
status,body,h=c.req('login',{'email':'admin@isam.local','password':'incorrecta'});check('CSRF rechaza formulario sin token',status==403)
for role,email,password in [('ADMIN','admin@isam.local','Admin#ISAM2026!'),('OPERADOR','operador@isam.local','Opera#ISAM2026!'),('CONSULTA','consulta@isam.local','Consulta#ISAM2026!')]:
 c=Client();_,body,_=c.req('login');token=c.token(body)
 status,body,h=c.req('login',{'_csrf':token,'email':email,'password':password});check(role+' inicia sesión y abre dashboard',status==200 and 'Evolución de ventas' in body)
 for route,params in [('catalog',{'module':'clientes'}),('catalog',{'module':'productos'}),('catalog',{'module':'categorias'}),('catalog',{'module':'proveedores'}),('orders',{}),('order-show',{'id':10248}),('reports',{}),('incidents',{}),('password',{})]:
  status,body,h=c.req(route,params=params);check(role+' GET '+route+' '+str(params),status==200 and 'Operación no completada' not in body)
 for route in ['imports','users','audit','backups']:
  status,body,h=c.req(route);allowed=role=='ADMIN' or (role=='OPERADOR' and route=='imports');check(role+' autorización '+route,status==(200 if allowed else 403))
 status,body,h=c.req('catalog-export',params={'module':'clientes','filter':'Germany'});check(role+' exporta CSV con filtro',status==200 and 'text/csv' in h.get('Content-Type','') and 'Germany' in body and 'Mexico' not in body)
 status,body,h=c.req('catalog-form',params={'module':'productos','id':1});check(role+' permiso edición de productos',status==(403 if role=='CONSULTA' else 200))
 if role!='CONSULTA':
  status,body,h=c.req('order-form',params={'id':10248});check(role+' formulario de pedido existente',status==200 and 'lines[0][ProductID]' in body)
 _,body,_=c.req('dashboard');token=c.token(body)
 if role!='ADMIN':
  status,_,_=c.req('catalog-delete',{'_csrf':token,'id':'ALFKI'},params={'module':'clientes'});check(role+' no puede eliminar',status==403)
 if role=='CONSULTA':
  status,_,_=c.req('catalog-save',{'_csrf':token,'CompanyName':'No autorizado'},params={'module':'proveedores'});check('CONSULTA no puede crear registros',status==403)
 status,_,_=c.req('catalog-save',{'_csrf':'alterado'},params={'module':'clientes'});check(role+' rechaza CSRF alterado',status==403)
 status,_,_=c.req('no-existe');check('Ruta inexistente devuelve 404',status==404)
 status,body,h=c.req('logout',{'_csrf':token});check(role+' cierra sesión',status==200 and 'Iniciar sesión' in body)

out=pathlib.Path(__file__).resolve().parent.parent/'build'/'http-results.json';out.parent.mkdir(exist_ok=True);out.write_text(json.dumps(checks,ensure_ascii=False,indent=2),encoding='utf-8')
print(f'{sum(x["passed"] for x in checks)}/{len(checks)} comprobaciones correctas.')
raise SystemExit(0 if all(x['passed'] for x in checks) else 1)
