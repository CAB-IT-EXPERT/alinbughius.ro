import fs from 'node:fs/promises';
import path from 'node:path';
import {FileBlob, SpreadsheetFile} from '@oai/artifact-tool';

const source = process.argv[2] || '.runtime/report-test.xlsx';
const outputDir = process.argv[3] || 'outputs/alinbughius_xlsx_export_20260921';
const previewDir = '.runtime/report-previews';
await fs.mkdir(outputDir, {recursive:true});
await fs.mkdir(previewDir, {recursive:true});

const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(source));
workbook.recalculate();

const sheets = await workbook.inspect({kind:'sheet', include:'id,name'});
const summary = await workbook.inspect({kind:'table', range:'Rezumat!A1:L20', include:'values,formulas', tableMaxRows:20, tableMaxCols:12});
const analysis = await workbook.inspect({kind:'formula', sheetId:'Analize', range:'A1:J30', maxChars:5000, options:{maxResults:100}});
const details = await workbook.inspect({kind:'table', range:'Programari!A1:R8', include:'values,formulas', tableMaxRows:8, tableMaxCols:18});
const errors = await workbook.inspect({kind:'match', searchTerm:'#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!', options:{useRegex:true,maxResults:300}, summary:'final formula error scan'});
console.log(sheets.ndjson);
console.log(summary.ndjson);
console.log(analysis.ndjson);
console.log(details.ndjson);
console.log(errors.ndjson);

for (const [sheetName, range] of [['Rezumat','A1:L20'], ['Analize','A1:J16'], ['Programari','A1:R8']]) {
  const preview = await workbook.render({sheetName, range, scale:1.4, format:'png'});
  const filename = path.join(previewDir, `${sheetName.toLowerCase()}.png`);
  await fs.writeFile(filename, new Uint8Array(await preview.arrayBuffer()));
}

const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(path.join(previewDir, 'artifact-roundtrip.xlsx'));
await fs.copyFile(source, path.join(outputDir, 'raport-programari-sample.xlsx'));
await fs.rm(path.join(outputDir, 'raport-programari-sample.xlsx.inspect.ndjson'), {force:true});
console.log(`PASS: raport verificat și exportat în ${outputDir}`);
