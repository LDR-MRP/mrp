<?php
/** XLSX OOXML nativo, sin dependencias de terceros. Uso: Ped_backorders::exportarExcel(). */
final class PedBackordersReporteXlsx
{
    private static function xml($s): string
    {
        return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
    private static function celda(string $col,int $fila,$valor,int $estilo=0,bool $numero=false): string
    {
        $ref=$col.$fila;
        if($numero){$n=(float)$valor;return '<c r="'.$ref.'" s="'.$estilo.'"><v>'.(is_finite($n)?$n:0).'</v></c>';}
        return '<c r="'.$ref.'" s="'.$estilo.'" t="inlineStr"><is><t xml:space="preserve">'.self::xml($valor).'</t></is></c>';
    }
    private static function fila(int $n,array $cells,?int $alto=null): string
    {
        return '<row r="'.$n.'"'.($alto?' ht="'.$alto.'" customHeight="1"':'').'>'.implode('',$cells).'</row>';
    }
    private static function fecha($v): string
    {
        $s=trim((string)$v);if(!$s)return 'Sin fecha';
        $dt=DateTimeImmutable::createFromFormat('Y-m-d',substr($s,0,10));
        return $dt?$dt->format('d/m/Y'):substr($s,0,10);
    }
    private static function mes($v): string
    {
        $s=(string)$v; if(!$s)return 'Sin mes';
        $names=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $n=(int)substr($s,5,2);return $n>=1&&$n<=12?$names[$n-1].' '.substr($s,0,4):$s;
    }
    public static function descargar(array $data,array $filtros): void
    {
        if(!class_exists('ZipArchive')) { self::descargarXlsCompatible($data,$filtros); return; }
        $zip=new ZipArchive();$archivo=tempnam(sys_get_temp_dir(),'bo_xlsx_');
        if(!$archivo||$zip->open($archivo,ZipArchive::OVERWRITE)!==true)throw new RuntimeException('No fue posible preparar el archivo XLSX.');
        $porPedido=[];foreach($data['detalles'] as $d){$porPedido[(string)$d['idpedido']][]=$d;}
        $fechaGen=(new DateTimeImmutable('now',new DateTimeZone('America/Mexico_City')))->format('d/m/Y H:i');
        $desFiltros=[];foreach($filtros as $key=>$v){if((string)$v!=='')$desFiltros[]=$key.': '.$v;}
        $filtroTxt=$desFiltros?implode(' | ',$desFiltros):'Sin filtros';
        $r=1;$filas=[];$merges=[];
        $filas[]=self::fila($r,[self::celda('C',$r,'REPORTE DE BACKORDERS',1),self::celda('G',$r,'Generado: '.$fechaGen,7)],34);
        $merges[]='A1:B2';$merges[]='C1:F2';$merges[]='G1:I1';
        $r++;$filas[]=self::fila($r,[self::celda('G',$r,'Filtros: '.$filtroTxt,7)],25);$merges[]='G2:I2';
        $r++;$filas[]=self::fila($r,[],10);
        $r++;$tot=$data['totales'];$filas[]=self::fila($r,[
            self::celda('A',$r,'PEDIDOS: '.($tot['pedidos']??0),8),
            self::celda('C',$r,'COMPROMETIDAS: '.($tot['comprometidas']??0),8),
            self::celda('E',$r,'ASIGNADAS: '.((int)($tot['asignadas']??0)),8),
            self::celda('G',$r,'PENDIENTES: '.($tot['pendientes']??0),8)],32);$merges[]='A4:B4';$merges[]='C4:D4';$merges[]='E4:F4';$merges[]='G4:I4';
        $r++;$filas[]=self::fila($r,[],11);
        foreach($data['pedidos'] as $pedido){
            $r++;$filas[]=self::fila($r,[self::celda('A',$r,($pedido['folio_pedido']?:$pedido['clave']).'  |  '.$pedido['distribuidor'].'  |  Backorder: '.self::fecha($pedido['fecha_backorder']).'  |  '.((int)$pedido['pendientes']>0?'Pendiente':'Asignado'),2)],25);$merges[]='A'.$r.':I'.$r;
            $r++;$filas[]=self::fila($r,[self::celda('A',$r,'Modelo / versión / SKU',3),self::celda('B',$r,'Color',3),self::celda('C',$r,'Comprometidas',3),self::celda('D',$r,'Asignadas',3),self::celda('E',$r,'Pendientes',3),self::celda('F',$r,'Entrega programada',3),self::celda('G',$r,'Mes a facturar',3),self::celda('H',$r,'Precio unitario',3),self::celda('I',$r,'Importe',3)],29);
            foreach($porPedido[(string)$pedido['idpedido']]??[] as $d){
                $r++;
                $nombre=trim(($d['unidad']??'').(!empty($d['modelo'])?' / '.$d['modelo']:'').(!empty($d['sku'])?' · SKU: '.$d['sku']:''));
                $filas[]=self::fila($r,[self::celda('A',$r,$nombre,0),self::celda('B',$r,$d['color']?:'—',0),self::celda('C',$r,$d['cantidad_comprometida'],6,true),self::celda('D',$r,(int)$d['cantidad_asignada'],6,true),self::celda('E',$r,$d['cantidad_pendiente'],6,true),self::celda('F',$r,self::fecha($d['entrega_programada']),0),self::celda('G',$r,self::mes($d['mes_facturar']),0),self::celda('H',$r,$d['precio_unitario'],10,true),self::celda('I',$r,$d['total'],10,true)],28);
            }
            $r++;$filas[]=self::fila($r,[self::celda('A',$r,'SUBTOTAL DEL PEDIDO',14),self::celda('C',$r,$pedido['comprometidas'],11,true),self::celda('D',$r,(int)$pedido['asignadas'],11,true),self::celda('E',$r,$pedido['pendientes'],11,true),self::celda('I',$r,$pedido['monto_comprometido'],12,true)],27);$merges[]='A'.$r.':B'.$r;
            $r++;$filas[]=self::fila($r,[],8);
        }
        $r++;$filas[]=self::fila($r,[self::celda('A',$r,'TOTAL GENERAL',15),self::celda('C',$r,$tot['comprometidas'],9,true),self::celda('D',$r,(int)$tot['asignadas'],9,true),self::celda('E',$r,$tot['pendientes'],9,true),self::celda('I',$r,$tot['monto_comprometido'],13,true)],31);$merges[]='A'.$r.':B'.$r;
        $logo=__DIR__.'/../Assets/images/logo-cotizaciones.png';$tieneLogo=is_file($logo);
        if (!$tieneLogo) { $filas[0]=self::fila(1,[self::celda('A',1,'FOTON',1),self::celda('C',1,'REPORTE DE BACKORDERS',1),self::celda('G',1,'Generado: '.$fechaGen,7)],34); }
        $drawing=$tieneLogo?'<drawing r:id="rId1"/>':'';
        $sheet='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols><col min="1" max="1" width="42" customWidth="1"/><col min="2" max="2" width="21" customWidth="1"/><col min="3" max="5" width="19" customWidth="1"/><col min="6" max="6" width="23" customWidth="1"/><col min="7" max="7" width="21" customWidth="1"/><col min="8" max="9" width="22" customWidth="1"/></cols><sheetData>'.implode('',$filas).'</sheetData><mergeCells count="'.count($merges).'">'.implode('',array_map(static fn($m)=>'<mergeCell ref="'.$m.'"/>',$merges)).'</mergeCells>'.$drawing.'</worksheet>';
        $styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="20"/><color rgb="FF0B2852"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FF153D72"/><name val="Calibri"/></font></fonts><fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0B2852"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0769C3"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDCEEFF"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEDF5FF"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="16"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="3" fillId="4" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="1" fontId="3" fillId="4" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="5" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="1" fontId="2" fillId="2" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="1" fontId="3" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="4" fontId="3" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="4" fontId="2" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $types='<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'.($tieneLogo?'<Default Extension="png" ContentType="image/png"/>':'').'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.($tieneLogo?'<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>':'').'</Types>';
        $zip->addFromString('[Content_Types].xml',$types);
        $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Backorders" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml',$styles);$zip->addFromString('xl/worksheets/sheet1.xml',$sheet);
        if($tieneLogo){
            $zip->addFile($logo,'xl/media/logo.png');
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>');
            $drawingXml='<?xml version="1.0"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from><xdr:ext cx="2200000" cy="600000"/><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="FOTON"/><xdr:cNvPicPr/></xdr:nvPicPr><xdr:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';
            $zip->addFromString('xl/drawings/drawing1.xml',$drawingXml);
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.png"/></Relationships>');
        }else{
            // Sin archivo de logo disponible: el título conserva la identidad en azul FOTON.
            $sheet=str_replace('<sheetData>','<sheetData>', $sheet);
        }
        $zip->close();
        $nombre='Reporte_Backorders_'.(new DateTimeImmutable('now',new DateTimeZone('America/Mexico_City')))->format('Y-m-d').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$nombre.'"');
        header('Content-Length: '.filesize($archivo));header('Cache-Control: no-store');
        readfile($archivo);@unlink($archivo);
    }
    /** Compatibilidad para hospedajes sin extensión ZIP: Excel 2003 XML (.xls). */
    private static function descargarXlsCompatible(array $data,array $filtros): void
    {
        $safe=static fn($v)=>htmlspecialchars((string)$v,ENT_XML1|ENT_QUOTES,'UTF-8');
        $fila=static function(array $values,string $style='')use($safe){
            $out='<Row>';
            foreach($values as $v){$out.='<Cell'.($style?' ss:StyleID="'.$style.'"':'').'><Data ss:Type="String">'.$safe($v).'</Data></Cell>';}
            return $out.'</Row>';
        };
        $h=<<<'XML'
<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Styles><Style ss:ID="Title"><Font ss:Bold="1" ss:Size="18" ss:Color="#0C2851"/></Style><Style ss:ID="Group"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0C2851" ss:Pattern="Solid"/></Style><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0769C3" ss:Pattern="Solid"/></Style><Style ss:ID="Subtotal"><Font ss:Bold="1" ss:Color="#0C2851"/><Interior ss:Color="#DCEEFF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style><Style ss:ID="GroupCenter"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0C2851" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style></Styles><Worksheet ss:Name="Backorders"><Table>
XML;
        $h.=$fila(['REPORTE DE BACKORDERS'],'Title');
        $h.=$fila(['Generado: '.date('d/m/Y H:i')]);
        $h.=$fila(['Filtros: '.implode(' | ',array_map(static fn($k,$v)=>$k.': '.$v,array_keys($filtros),array_values($filtros)))]);
        $h.=$fila(['Pedidos: '.($data['totales']['pedidos']??0),'Comprometidas: '.($data['totales']['comprometidas']??0),'Asignadas: '.((int)($data['totales']['asignadas']??0)),'Pendientes: '.($data['totales']['pendientes']??0)],'Subtotal');
        $detalles=[];foreach($data['detalles'] as $d)$detalles[(string)$d['idpedido']][]=$d;
        foreach($data['pedidos'] as $p){
            $h.=$fila([($p['folio_pedido']?:$p['clave']).' | '.$p['distribuidor'].' | Backorder: '.self::fecha($p['fecha_backorder'])],'Group');
            $h.=$fila(['Modelo / versión / SKU','Color','Comprometidas','Asignadas','Pendientes','Entrega programada','Mes a facturar','Precio unitario','Importe'],'Header');
            foreach($detalles[(string)$p['idpedido']]??[] as $d){$h.=$fila([($d['unidad']??'').' / '.($d['modelo']??'').' - SKU: '.($d['sku']??''),$d['color']??'',(string)$d['cantidad_comprometida'],(string)((int)$d['cantidad_asignada']),(string)$d['cantidad_pendiente'],self::fecha($d['entrega_programada']),self::mes($d['mes_facturar']),number_format((float)$d['precio_unitario'],2,'.',','),number_format((float)$d['total'],2,'.',',')]);}
            $h.=$fila(['SUBTOTAL DEL PEDIDO','',(string)$p['comprometidas'],(string)((int)$p['asignadas']),(string)$p['pendientes'],'','','',number_format((float)$p['monto_comprometido'],2,'.',',')],'Subtotal');
        }
        $t=$data['totales'];$h.=$fila(['TOTAL GENERAL','',(string)$t['comprometidas'],(string)((int)$t['asignadas']),(string)$t['pendientes'],'','','',number_format((float)$t['monto_comprometido'],2,'.',',')],'GroupCenter');
        $h.='</Table></Worksheet></Workbook>';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="Reporte_Backorders_'.date('Y-m-d').'.xls"');
        header('Cache-Control: no-store');echo $h;
    }

}
