-- Renombra los logros de una base de datos que ya existe (no hace falta si importas BBDD.sql desde cero).
-- Ejecútalo una vez en phpMyAdmin (pestaña SQL) con la base de datos k_libro seleccionada.
-- Los logros que los usuarios ya hayan conseguido se conservan.

UPDATE logros SET nombre = 'Lector Principiante' WHERE nombre = 'Lector Iniciado';
UPDATE logros SET nombre = 'Bibliófilo'          WHERE nombre = 'Devorador de Mundos';
UPDATE logros SET nombre = 'Reto Mensual', descripcion = 'Has completado tu reto de lectura del mes.' WHERE nombre = 'Campeón Mensual';
