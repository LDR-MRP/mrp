SELECT '--- TABLAS FALTANTES EN PRODUCCION (u546825723_dbmrp) ---' as info;
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'db_mrp' AND table_name NOT IN (
    SELECT table_name FROM information_schema.tables WHERE table_schema = 'prod_mrp'
);

SELECT '--- TABLAS QUE ESTAN EN PRODUCCION PERO NO EN LOCAL ---' as info;
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'prod_mrp' AND table_name NOT IN (
    SELECT table_name FROM information_schema.tables WHERE table_schema = 'db_mrp'
);

SELECT '--- COLUMNAS FALTANTES/DIFERENTES EN PRODUCCION ---' as info;
SELECT 
    t1.table_name, 
    t1.column_name, 
    t1.column_type as local_type, 
    IFNULL(t2.column_type, 'FALTA EN PROD') as prod_type 
FROM information_schema.columns t1 
LEFT JOIN information_schema.columns t2 
    ON t1.table_name = t2.table_name 
    AND t1.column_name = t2.column_name 
    AND t2.table_schema = 'prod_mrp' 
WHERE t1.table_schema = 'db_mrp' 
AND (t2.column_name IS NULL OR t1.column_type != t2.column_type);
