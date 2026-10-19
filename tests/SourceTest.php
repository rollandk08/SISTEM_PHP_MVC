<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class SourceTest extends TestCase {
    public function testAllOriginalInsertStatementsAreUnchanged(): void {
        $source=file_get_contents(ROOT.'/database/source/northwind.original.sql');$adapted=file_get_contents(ROOT.'/migrations/001_northwind.sql');
        preg_match_all('/INSERT INTO .*?;(?=\r?\n|$)/s',$source,$before);preg_match_all('/INSERT INTO .*?;(?=\r?\n|$)/s',$adapted,$after);
        $normalize=static fn($s)=>str_replace("\r\n","\n",$s);
        $this->assertNotEmpty($before[0]);$this->assertSame(array_map($normalize,$before[0]),array_map($normalize,$after[0]));
    }
    public function testOriginalSourceAndMigrationHashesMatchManifest(): void {$m=json_decode(file_get_contents(ROOT.'/database/source/manifest.json'),true);$this->assertSame($m['original_sha256'],hash_file('sha256',ROOT.'/database/source/northwind.original.sql'));$this->assertSame($m['migration_sha256'],hash_file('sha256',ROOT.'/migrations/001_northwind.sql'));}
}
