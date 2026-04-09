-- Migracion opcional: idformulario 14 duplicado de 70 (inscripcion examen web).
-- Ejecutar con backup previo. Evita filas duplicadas (usuario+70) si ya existia permiso en 70.

UPDATE llavesxform SET idformulario = 70 WHERE idformulario = 14;
