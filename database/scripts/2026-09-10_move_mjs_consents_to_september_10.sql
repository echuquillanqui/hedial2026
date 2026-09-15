-- Corrige únicamente los consentimientos del 09/09/2026 de pacientes M-J-S.
-- Requiere MySQL 8.0+. Ejecución sugerida:
--   mysql --user=USUARIO --password BASE_DE_DATOS \
--     < database/scripts/2026-09-10_move_mjs_consents_to_september_10.sql
--
-- `hemodialysis_consents.patient_id` referencia `patients.id`. La actualización no
-- modifica ninguna llave foránea; el JOIN se usa para limitarla por `secuencia`.
-- La hora original se conserva para respetar el índice único compuesto por
-- (patient_id, consented_at, version).

SET @source_date := DATE('2026-09-09');
SET @target_date := DATE('2026-09-10');
SET @patient_sequence := 'M-J-S';

-- Verificación informativa de las llaves foráneas reales de la tabla.
SELECT
    kcu.CONSTRAINT_NAME,
    kcu.COLUMN_NAME,
    kcu.REFERENCED_TABLE_NAME,
    kcu.REFERENCED_COLUMN_NAME,
    rc.UPDATE_RULE,
    rc.DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE AS kcu
JOIN information_schema.REFERENTIAL_CONSTRAINTS AS rc
  ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
 AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
WHERE kcu.TABLE_SCHEMA = DATABASE()
  AND kcu.TABLE_NAME = 'hemodialysis_consents'
  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY kcu.CONSTRAINT_NAME;

DROP PROCEDURE IF EXISTS move_mjs_consents_to_september_10;
DELIMITER $$

CREATE PROCEDURE move_mjs_consents_to_september_10()
main: BEGIN
    DECLARE target_count BIGINT UNSIGNED DEFAULT 0;
    DECLARE updated_count BIGINT UNSIGNED DEFAULT 0;
    DECLARE orphan_count BIGINT UNSIGNED DEFAULT 0;
    DECLARE conflict_count BIGINT UNSIGNED DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Bloquea y fija exactamente las filas que serán corregidas.
    DROP TEMPORARY TABLE IF EXISTS consent_date_correction_targets;
    CREATE TEMPORARY TABLE consent_date_correction_targets (
        id BIGINT UNSIGNED PRIMARY KEY,
        old_consented_at TIMESTAMP NOT NULL,
        new_consented_at TIMESTAMP NOT NULL
    ) ENGINE=InnoDB;

    INSERT INTO consent_date_correction_targets (id, old_consented_at, new_consented_at)
    SELECT
        hc.id,
        hc.consented_at,
        TIMESTAMP(@target_date, TIME(hc.consented_at))
    FROM hemodialysis_consents AS hc
    JOIN patients AS p ON p.id = hc.patient_id
    WHERE hc.consented_at >= @source_date
      AND hc.consented_at < DATE_ADD(@source_date, INTERVAL 1 DAY)
      AND p.secuencia = @patient_sequence
    FOR UPDATE;

    SELECT COUNT(*) INTO target_count
    FROM consent_date_correction_targets;

    -- Una fila huérfana indicaría que la FK no se aplicó correctamente en esta BD.
    SELECT COUNT(*) INTO orphan_count
    FROM hemodialysis_consents AS hc
    LEFT JOIN patients AS p ON p.id = hc.patient_id
    WHERE hc.consented_at >= @source_date
      AND hc.consented_at < DATE_ADD(@source_date, INTERVAL 1 DAY)
      AND p.id IS NULL;

    IF orphan_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Abortado: existen consentimientos del 09/09/2026 sin paciente válido.';
    END IF;

    -- Evita violar consent_patient_date_version_unique al mover el timestamp.
    SELECT COUNT(*) INTO conflict_count
    FROM consent_date_correction_targets AS target
    JOIN hemodialysis_consents AS source ON source.id = target.id
    JOIN hemodialysis_consents AS existing
      ON existing.patient_id = source.patient_id
     AND existing.consented_at = target.new_consented_at
     AND existing.version = source.version
     AND existing.id <> source.id;

    IF conflict_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Abortado: la fecha destino genera conflicto con el índice único de consentimientos.';
    END IF;

    -- Vista previa incluida en la salida del cliente MySQL.
    SELECT
        hc.id AS consent_id,
        hc.patient_id,
        p.secuencia,
        target.old_consented_at,
        target.new_consented_at,
        hc.version
    FROM consent_date_correction_targets AS target
    JOIN hemodialysis_consents AS hc ON hc.id = target.id
    JOIN patients AS p ON p.id = hc.patient_id
    ORDER BY hc.patient_id, hc.id;

    UPDATE hemodialysis_consents AS hc
    JOIN consent_date_correction_targets AS target ON target.id = hc.id
    SET hc.consented_at = target.new_consented_at;

    SET updated_count = ROW_COUNT();

    IF updated_count <> target_count THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Abortado: no se actualizaron todas las filas seleccionadas.';
    END IF;

    COMMIT;

    SELECT
        target_count AS consentimientos_actualizados,
        @source_date AS fecha_anterior,
        @target_date AS fecha_nueva,
        @patient_sequence AS secuencia;
END$$

DELIMITER ;
CALL move_mjs_consents_to_september_10();
DROP PROCEDURE move_mjs_consents_to_september_10;
