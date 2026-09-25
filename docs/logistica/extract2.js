const xlsx = require('xlsx');
const wb = xlsx.readFile('VINS pruebas cuasireales.xlsx');
const ws = wb.Sheets[wb.SheetNames[0]];
const data = xlsx.utils.sheet_to_json(ws);
const distMap = {};
data.forEach(row => {
    const dist = row.DISTRIBUIDOR;
    let km = row.Km2;
    const dest = row.DESTINO;
    if (!dist) return;
    if (km === 'N/A' || km === undefined) km = 0;
    if (!distMap[dist]) distMap[dist] = {};
    if (!distMap[dist][km]) distMap[dist][km] = new Set();
    if (dest) distMap[dist][km].add(dest);
});

for (const [dist, kmsMap] of Object.entries(distMap)) {
    const kms = Object.keys(kmsMap);
    if (kms.length > 1) {
        console.log(dist);
        kms.forEach((k, idx) => console.log('  Km:', k, ' Sede', idx+1, ' Destinos:', Array.from(kmsMap[k]).join(', ')));
    }
}
