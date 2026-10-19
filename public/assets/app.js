'use strict';
document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',e=>{if(!confirm(form.dataset.confirm))e.preventDefault();}));
const toggle=document.getElementById('menu-toggle');if(toggle)toggle.addEventListener('click',()=>{const open=document.getElementById('sidebar').classList.toggle('open');toggle.setAttribute('aria-expanded',String(open));});
const dashboard=document.getElementById('dashboard-data');
if(dashboard && typeof Chart!=='undefined'){
 const data=JSON.parse(dashboard.textContent);Chart.defaults.font.family='Segoe UI, Arial, sans-serif';Chart.defaults.font.size=10;Chart.defaults.color='#91a2b3';
 new Chart(document.getElementById('sales-chart'),{type:'line',data:{labels:data.monthly.map(r=>r.month),datasets:[{label:'Venta neta USD',data:data.monthly.map(r=>Number(r.total)),borderColor:'#2c9f9b',backgroundColor:'#2c9f9b10',fill:true,tension:.35,borderWidth:2,pointRadius:2,pointBackgroundColor:'#2c9f9b'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>'$ '+c.parsed.y.toLocaleString('es-PE')}}},scales:{x:{grid:{display:false},border:{display:false},ticks:{maxTicksLimit:6}},y:{border:{display:false},grid:{color:'#f0f3f6'},ticks:{callback:v=>'$ '+Number(v)/1000+'k'}}}}});
 new Chart(document.getElementById('category-chart'),{type:'doughnut',data:{labels:data.categories.map(r=>r.label),datasets:[{data:data.categories.map(r=>Number(r.total)),backgroundColor:['#278f91','#68b8ae','#a2d5c1','#f0cf97','#d9ae7c','#809bc0','#b1bed3','#d6e3eb'],borderWidth:3,borderColor:'#fff',hoverOffset:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{position:'bottom',labels:{boxWidth:8,boxHeight:8,padding:13,usePointStyle:true,font:{size:9}}}}}});
}
const lines=document.getElementById('order-lines');
if(lines){
 let index=lines.children.length;
 const add=()=>{if(lines.children.length>=100)return;const html=document.getElementById('line-template').innerHTML.replaceAll('__INDEX__',String(index++));lines.insertAdjacentHTML('beforeend',html);};
 document.getElementById('add-line').addEventListener('click',add);
 lines.addEventListener('click',e=>{if(e.target.closest('.remove-line'))e.target.closest('tr').remove();});
 lines.addEventListener('change',e=>{if(e.target.matches('.line-product'))e.target.closest('tr').querySelector('.line-price').value=e.target.selectedOptions[0].dataset.price||'0';});
 if(!lines.children.length)add();
}
