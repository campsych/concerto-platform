CREATE OR REPLACE FUNCTION convert_to_bigint(input_value text)
RETURNS BIGINT AS $$
DECLARE result_value BIGINT DEFAULT 0;
BEGIN
    BEGIN
        result_value := input_value::BIGINT;
    EXCEPTION WHEN OTHERS THEN
        RAISE NOTICE 'Cannot cast text "%" to BIGINT, returning 0', input_value;
        RETURN 0;
    END;
RETURN result_value;
END;
$$ LANGUAGE plpgsql;

CREATE CAST (text AS BIGINT) WITH FUNCTION convert_to_bigint( text ) AS IMPLICIT;
