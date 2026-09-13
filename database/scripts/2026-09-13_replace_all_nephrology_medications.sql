-- Reemplaza los medicamentos de TODAS las consultas nefrológicas.
-- Ejecución:
--   mysql --user=USUARIO --password BASE_DE_DATOS \
--     < database/scripts/2026-09-13_replace_all_nephrology_medications.sql

START TRANSACTION;

DELETE FROM medications;

INSERT INTO medications (
    nephrology_consultation_id,
    fua_code,
    description,
    c,
    prescribed_quantity,
    delivered_quantity,
    created_at,
    updated_at
)
SELECT
    consultations.id,
    defaults.fua_code,
    defaults.description,
    defaults.indication,
    defaults.quantity,
    defaults.quantity,
    NOW(),
    NOW()
FROM nephrology_consultations AS consultations
CROSS JOIN (
    SELECT '06127' AS fua_code, 'Tiamina clorhidrato 100 mg tableta' AS description, '1 tableta cada 24 horas en el desayuno' AS indication, 30 AS quantity
    UNION ALL
    SELECT '05491', 'Piridoxina clorhidrato 50 mg tableta', '1 tableta cada 24 horas en el desayuno', 30
    UNION ALL
    SELECT '00200', 'Ácido fólico 500 mcg (0.5 mg) tableta', '1 tableta cada 24 horas en el desayuno', 30
    UNION ALL
    SELECT '00671', 'Amlodipino (como Besilato) 10 mg tableta', '1 tableta cada 24 horas, 9 AM', 30
    UNION ALL
    SELECT '04523', 'Losartan 50 mg tableta', '1 tableta cada 12 horas, 8 AM y 8 PM', 60
) AS defaults;

COMMIT;

SELECT COUNT(*) AS consultas_actualizadas
FROM nephrology_consultations;

SELECT COUNT(*) AS medicamentos_insertados
FROM medications;
