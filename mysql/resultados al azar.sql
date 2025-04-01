UPDATE db_Partidas
SET resultado = (
    CASE 
        WHEN RAND() < 0.333 THEN '1-0'
        WHEN RAND() < 0.666 THEN '0-1'
        ELSE '½-½'
    END
)
WHERE resultado IS NULL;


UPDATE db_Partidas SET resultado = NULL;
