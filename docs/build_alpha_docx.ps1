$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$base = Split-Path -Parent $MyInvocation.MyCommand.Path
$mdPath = Join-Path $base 'Capstone-Alpha-Testing-Document.md'
$docxPath = Join-Path $base 'Capstone-Alpha-Testing-Document.docx'
$markdown = [IO.File]::ReadAllText($mdPath, [Text.Encoding]::UTF8)

function XmlEscape([string]$s) {
    return [System.Security.SecurityElement]::Escape($s)
}

function Add-Run([string]$s, [bool]$bold = $false, [int]$size = 20) {
    if ($s.Length -eq 0) { return '' }
    $props = "<w:sz w:val=`"$size`"/>"
    if ($bold) { $props += '<w:b/>' }
    return "<w:r><w:rPr>$props</w:rPr><w:t xml:space=`"preserve`">$(XmlEscape $s)</w:t></w:r>"
}

function Inline-Xml([string]$s) {
    $builder = [Text.StringBuilder]::new()
    $parts = [regex]::Split($s, '(\*\*.*?\*\*)')
    foreach ($part in $parts) {
        if ($part.StartsWith('**') -and $part.EndsWith('**') -and $part.Length -ge 4) {
            [void]$builder.Append((Add-Run $part.Substring(2, $part.Length - 4) $true))
        } else {
            $plain = [regex]::Replace($part, '`([^`]+)`', '$1')
            [void]$builder.Append((Add-Run $plain))
        }
    }
    return $builder.ToString()
}

function Paragraph([string]$text, [string]$style = 'Normal', [string]$extra = '') {
    $content = Inline-Xml $text
    if ($text -match '^\s*$') { $content = '<w:r><w:t> </w:t></w:r>' }
    return "<w:p><w:pPr><w:pStyle w:val=`"$style`"/>$extra</w:pPr>$content</w:p>"
}

function Table-Cell([string]$text, [bool]$header = $false) {
    $runs = Inline-Xml $text
    if ($header) { $runs = [regex]::Replace($runs, '<w:rPr>', '<w:rPr><w:b/>') }
    return "<w:tc><w:tcPr><w:tcW w:w=`"0`" w:type=`"auto`"/><w:shd w:fill=`"$(if ($header) {'DCEAF2'} else {'FFFFFF'})`"/></w:tcPr><w:p><w:pPr><w:spacing w:after=`"40`"/></w:pPr>$runs</w:p></w:tc>"
}

function Make-Table([string[]]$rows) {
    $xml = '<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="0" w:type="auto"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="B7C9D3"/><w:left w:val="single" w:sz="4" w:color="B7C9D3"/><w:bottom w:val="single" w:sz="4" w:color="B7C9D3"/><w:right w:val="single" w:sz="4" w:color="B7C9D3"/><w:insideH w:val="single" w:sz="4" w:color="D5E0E5"/><w:insideV w:val="single" w:sz="4" w:color="D5E0E5"/></w:tblBorders></w:tblPr>'
    $index = 0
    foreach ($row in $rows) {
        $cells = @($row.Trim().Trim('|').Split('|') | ForEach-Object { $_.Trim() })
        if ($cells.Count -gt 0 -and $cells[0] -match '^:?-{3,}:?$') { continue }
        $xml += '<w:tr>'
        foreach ($cell in $cells) { $xml += Table-Cell $cell ($index -eq 0) }
        $xml += '</w:tr>'
        $index++
    }
    return $xml + '</w:tbl>'
}

$body = [Text.StringBuilder]::new()
$lines = $markdown -split "`r?`n"
$inCode = $false
$codeLines = [Collections.Generic.List[string]]::new()
for ($i = 0; $i -lt $lines.Length; $i++) {
    $line = $lines[$i]
    if ($line.Trim().StartsWith('```')) {
        if (-not $inCode) { $inCode = $true; $codeLines.Clear() }
        else {
            foreach ($codeLine in $codeLines) { [void]$body.Append((Paragraph $codeLine 'Code')) }
            $inCode = $false
        }
        continue
    }
    if ($inCode) { $codeLines.Add($line); continue }
    if ($line -match '^\s*\|') {
        $tableLines = [Collections.Generic.List[string]]::new()
        while ($i -lt $lines.Length -and $lines[$i] -match '^\s*\|') { $tableLines.Add($lines[$i]); $i++ }
        $i--
        [void]$body.Append((Make-Table $tableLines.ToArray()))
        continue
    }
    if ($line -match '^\s*---+\s*$') { [void]$body.Append('<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="8" w:color="447A91"/></w:pBdr></w:pPr></w:p>'); continue }
    if ($line -match '^# (.*)$') { [void]$body.Append((Paragraph $Matches[1] 'Title')); continue }
    if ($line -match '^## (.*)$') { [void]$body.Append((Paragraph $Matches[1] 'Heading1')); continue }
    if ($line -match '^### (.*)$') { [void]$body.Append((Paragraph $Matches[1] 'Heading2')); continue }
    if ($line -match '^>\s?(.*)$') { [void]$body.Append((Paragraph $Matches[1] 'Quote')); continue }
    if ($line -match '^\s*-\s+(.*)$') { [void]$body.Append((Paragraph $Matches[1] 'Normal' '<w:ind w:left="360" w:hanging="180"/>')); continue }
    if ($line.Trim()) { [void]$body.Append((Paragraph $line 'Normal')) }
}

$styles = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:sz w:val="20"/><w:color w:val="243746"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="276" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>
<w:style w:type="paragraph" w:styleId="Normal"><w:name w:val="Normal"/></w:style>
<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:rPr><w:b/><w:sz w:val="36"/><w:color w:val="164E63"/></w:rPr><w:pPr><w:spacing w:after="180"/></w:pPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="164E63"/></w:rPr><w:pPr><w:keepNext/><w:spacing w:before="240" w:after="100"/></w:pPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:rPr><w:b/><w:sz w:val="23"/><w:color w:val="276678"/></w:rPr><w:pPr><w:keepNext/><w:spacing w:before="180" w:after="80"/></w:pPr></w:style>
<w:style w:type="paragraph" w:styleId="Quote"><w:name w:val="Quote"/><w:basedOn w:val="Normal"/><w:rPr><w:i/><w:sz w:val="18"/><w:color w:val="52636D"/></w:rPr><w:pPr><w:ind w:left="240" w:right="240"/></w:pPr></w:style>
<w:style w:type="paragraph" w:styleId="Code"><w:name w:val="Code"/><w:basedOn w:val="Normal"/><w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:sz w:val="17"/><w:color w:val="31515D"/></w:rPr><w:pPr><w:spacing w:after="0" w:line="220" w:lineRule="auto"/></w:pPr></w:style>
<w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4" w:color="B7C9D3"/><w:left w:val="single" w:sz="4" w:color="B7C9D3"/><w:bottom w:val="single" w:sz="4" w:color="B7C9D3"/><w:right w:val="single" w:sz="4" w:color="B7C9D3"/><w:insideH w:val="single" w:sz="4" w:color="D5E0E5"/><w:insideV w:val="single" w:sz="4" w:color="D5E0E5"/></w:tblBorders></w:tblPr></w:style>
</w:styles>
'@
$documentXml = "<?xml version=`"1.0`" encoding=`"UTF-8`" standalone=`"yes`"?><w:document xmlns:w=`"http://schemas.openxmlformats.org/wordprocessingml/2006/main`"><w:body>$($body.ToString())<w:sectPr><w:pgSz w:w=`"12240`" w:h=`"15840`"/><w:pgMar w:top=`"900`" w:right=`"900`" w:bottom=`"900`" w:left=`"900`" w:header=`"450`" w:footer=`"450`" w:gutter=`"0`"/></w:sectPr></w:body></w:document>"
$contentTypes = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>
'@
$rels = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>
'@
$documentRels = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>
'@

$temp = Join-Path $env:TEMP ('alpha-docx-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path (Join-Path $temp '_rels') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $temp 'word\_rels') -Force | Out-Null
[IO.File]::WriteAllText((Join-Path $temp '[Content_Types].xml'), $contentTypes, [Text.UTF8Encoding]::new($false))
[IO.File]::WriteAllText((Join-Path $temp '_rels\.rels'), $rels, [Text.UTF8Encoding]::new($false))
[IO.File]::WriteAllText((Join-Path $temp 'word\document.xml'), $documentXml, [Text.UTF8Encoding]::new($false))
[IO.File]::WriteAllText((Join-Path $temp 'word\styles.xml'), $styles, [Text.UTF8Encoding]::new($false))
[IO.File]::WriteAllText((Join-Path $temp 'word\_rels\document.xml.rels'), $documentRels, [Text.UTF8Encoding]::new($false))
if (Test-Path -LiteralPath $docxPath) { Remove-Item -LiteralPath $docxPath -Force }
[IO.Compression.ZipFile]::CreateFromDirectory($temp, $docxPath)
Remove-Item -LiteralPath $temp -Recurse -Force
Get-Item -LiteralPath $docxPath | Select-Object FullName, Length
