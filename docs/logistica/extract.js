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
    if (!distMap[dist]) distMap[dist] = new Set();
    distMap[dist].add(km);
});

for (const [dist, kms] of Object.entries(distMap)) {
    if (kms.size > 1) {
        console.log(dist, Array.from(kms));
    }
}
