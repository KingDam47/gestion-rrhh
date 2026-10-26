-- Datos demo — Gestión RR. HH.
-- ADVERTENCIA: este script es DESTRUCTIVO. Elimina todos los datos de las tablas
-- y solo debe ejecutarse en desarrollo o en una demo controlada.
-- Todos los datos son ficticios. No usar información real.
--
-- Credenciales demo:
--   admin@gestion-rrhh.test / password123
--   rrhh@gestion-rrhh.test  / password123
-- Cambiar la contraseña en cualquier entorno que no sea local.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE applications;
TRUNCATE TABLE candidates;
TRUNCATE TABLE vacancies;
TRUNCATE TABLE leave_requests;
TRUNCATE TABLE attendance_records;
TRUNCATE TABLE employees;
TRUNCATE TABLE positions;
TRUNCATE TABLE departments;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Usuarios
-- Hash bcrypt de la contraseña literal "password123" (coste 10).
-- ---------------------------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, role, is_active, last_login_at, created_at, updated_at) VALUES
(1, 'Administrador Demo', 'admin@gestion-rrhh.test', '$2y$10$Uj1LIQmReMzBbfTxfHISL.nceuPa2fCUudzmO.cdvh4e2eBIiDhl2', 'admin', 1, '2026-10-24 08:00:00', '2026-10-01 08:00:00', '2026-10-24 08:00:00'),
(2, 'Ana López', 'rrhh@gestion-rrhh.test', '$2y$10$Uj1LIQmReMzBbfTxfHISL.nceuPa2fCUudzmO.cdvh4e2eBIiDhl2', 'hr', 1, '2026-10-24 08:05:00', '2026-10-01 08:00:00', '2026-10-24 08:05:00');

-- ---------------------------------------------------------------------------
-- Departamentos
-- ---------------------------------------------------------------------------
INSERT INTO departments (id, name, description, is_active, created_at, updated_at) VALUES
(1, 'Administración', 'Área administrativa y de apoyo a la dirección.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(2, 'Recursos Humanos', 'Gestión del talento y procesos de personal.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(3, 'Tecnología', 'Desarrollo, soporte e infraestructura.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(4, 'Ventas', 'Comercialización y atención a clientes.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(5, 'Operaciones', 'Logística y operación diaria.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00');

-- ---------------------------------------------------------------------------
-- Cargos
-- ---------------------------------------------------------------------------
INSERT INTO positions (id, department_id, name, description, is_active, created_at, updated_at) VALUES
(1, 1, 'Asistente Administrativo', 'Apoyo administrativo general.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(2, 2, 'Gerente de RR. HH.', 'Responsable del área de Gestión Humana.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(3, 2, 'Analista de RR. HH.', 'Análisis y gestión de procesos de personal.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(4, 3, 'Desarrollador de Software', 'Desarrollo de aplicaciones internas.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(5, 3, 'Soporte Técnico', 'Atención de incidencias tecnológicas.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(6, 4, 'Ejecutivo de Ventas', 'Gestión comercial y de clientes.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00'),
(7, 5, 'Supervisor de Operaciones', 'Supervisión de la operación diaria.', 1, '2026-10-01 08:00:00', '2026-10-01 08:00:00');

-- ---------------------------------------------------------------------------
-- Empleados
-- ---------------------------------------------------------------------------
INSERT INTO employees (id, document, first_name, last_name, email, phone, hire_date, department_id, position_id, status, created_by, updated_by, created_at, updated_at) VALUES
(1, 'DEMO-0001', 'Laura', 'Gómez', 'laura.gomez@example.test', '555-0101', '2022-03-15', 2, 3, 'active', 1, 1, '2026-10-01 08:10:00', '2026-10-01 08:10:00'),
(2, 'DEMO-0002', 'Carlos', 'Pérez', 'carlos.perez@example.test', '555-0102', '2021-08-01', 3, 4, 'active', 1, 1, '2026-10-01 08:10:00', '2026-10-01 08:10:00'),
(3, 'DEMO-0003', 'María', 'Rodríguez', 'maria.rodriguez@example.test', '555-0103', '2023-01-10', 4, 6, 'active', 1, 1, '2026-10-01 08:10:00', '2026-10-01 08:10:00'),
(4, 'DEMO-0004', 'José', 'Martínez', 'jose.martinez@example.test', '555-0104', '2020-06-22', 5, 7, 'active', 1, 1, '2026-10-01 08:10:00', '2026-10-01 08:10:00'),
(5, 'DEMO-0005', 'Ana', 'Torres', 'ana.torres@example.test', '555-0105', '2024-02-05', 1, 1, 'active', 1, 1, '2026-10-01 08:10:00', '2026-10-01 08:10:00'),
(6, 'DEMO-0006', 'Luis', 'Ramírez', 'luis.ramirez@example.test', '555-0106', '2022-11-14', 3, 5, 'inactive', 1, 1, '2026-10-01 08:10:00', '2026-10-10 09:00:00'),
(7, 'DEMO-0007', 'Sofía', 'Herrera', 'sofia.herrera@example.test', '555-0107', '2019-09-30', 2, 2, 'active', 1, 1, '2026-10-01 08:10:00', '2026-10-01 08:10:00'),
(8, 'DEMO-0008', 'Diego', 'Castro', 'diego.castro@example.test', '555-0108', '2023-07-17', 4, 6, 'active', 1, 1, '2026-10-01 08:10:00', '2026-10-01 08:10:00'),
(9, 'DEMO-0009', 'Valentina', 'Ríos', 'valentina.rios@example.test', '555-0109', '2024-05-06', 1, 1, 'active', 1, 1, '2026-10-01 08:10:00', '2026-10-01 08:10:00'),
(10, 'DEMO-0010', 'Andrés', 'Mora', 'andres.mora@example.test', '555-0110', '2021-12-01', 5, 7, 'inactive', 1, 1, '2026-10-01 08:10:00', '2026-10-12 11:30:00');

-- ---------------------------------------------------------------------------
-- Asistencia del mes de octubre de 2026
-- ---------------------------------------------------------------------------
INSERT INTO attendance_records (id, employee_id, work_date, check_in, check_out, status, notes, created_by, updated_by, created_at, updated_at) VALUES
(1, 1, '2026-10-01', '08:00:00', '17:00:00', 'present', NULL, 2, 2, '2026-10-01 17:05:00', '2026-10-01 17:05:00'),
(2, 2, '2026-10-01', '08:15:00', '17:05:00', 'late', 'Ingreso con 15 minutos de tardanza.', 2, 2, '2026-10-01 17:06:00', '2026-10-01 17:06:00'),
(3, 3, '2026-10-01', '08:00:00', '17:00:00', 'present', NULL, 2, 2, '2026-10-01 17:07:00', '2026-10-01 17:07:00'),
(4, 4, '2026-10-02', '08:05:00', '17:10:00', 'present', NULL, 2, 2, '2026-10-02 17:11:00', '2026-10-02 17:11:00'),
(5, 5, '2026-10-02', '08:00:00', '16:55:00', 'present', NULL, 2, 2, '2026-10-02 16:56:00', '2026-10-02 16:56:00'),
(6, 1, '2026-10-02', '08:00:00', '17:00:00', 'present', NULL, 2, 2, '2026-10-02 17:01:00', '2026-10-02 17:01:00'),
(7, 2, '2026-10-02', NULL, NULL, 'absent', 'Ausencia no justificada.', 2, 2, '2026-10-02 18:00:00', '2026-10-02 18:00:00'),
(8, 3, '2026-10-05', '08:10:00', '17:00:00', 'late', 'Ingreso con 10 minutos de tardanza.', 2, 2, '2026-10-05 17:02:00', '2026-10-05 17:02:00'),
(9, 4, '2026-10-05', '08:00:00', '17:00:00', 'present', NULL, 2, 2, '2026-10-05 17:03:00', '2026-10-05 17:03:00'),
(10, 5, '2026-10-05', '08:00:00', '17:00:00', 'present', NULL, 2, 2, '2026-10-05 17:04:00', '2026-10-05 17:04:00'),
(11, 1, '2026-10-06', '08:00:00', '17:00:00', 'present', NULL, 2, 2, '2026-10-06 17:05:00', '2026-10-06 17:05:00'),
(12, 2, '2026-10-06', '08:00:00', '17:00:00', 'present', NULL, 2, 2, '2026-10-06 17:06:00', '2026-10-06 17:06:00'),
(13, 3, '2026-10-06', NULL, NULL, 'absent', 'Ausencia no justificada.', 2, 2, '2026-10-06 18:00:00', '2026-10-06 18:00:00'),
(14, 4, '2026-10-07', '08:20:00', '17:00:00', 'late', 'Ingreso con 20 minutos de tardanza.', 2, 2, '2026-10-07 17:01:00', '2026-10-07 17:01:00'),
(15, 5, '2026-10-07', '08:00:00', '17:00:00', 'present', NULL, 2, 2, '2026-10-07 17:02:00', '2026-10-07 17:02:00');

-- ---------------------------------------------------------------------------
-- Solicitudes de permiso
-- ---------------------------------------------------------------------------
INSERT INTO leave_requests (id, employee_id, leave_type, start_date, end_date, reason, status, reviewed_by, reviewed_at, review_notes, created_by, created_at, updated_at) VALUES
(1, 2, 'vacation', '2026-11-02', '2026-11-06', 'Vacaciones familiares.', 'pending', NULL, NULL, NULL, 2, '2026-10-20 09:00:00', '2026-10-20 09:00:00'),
(2, 1, 'personal', '2026-10-15', '2026-10-15', 'Trámite personal.', 'approved', 2, '2026-10-10 10:00:00', 'Permiso aprobado.', 2, '2026-10-08 08:30:00', '2026-10-10 10:00:00'),
(3, 3, 'vacation', '2026-10-20', '2026-10-22', 'Viaje familiar.', 'rejected', 2, '2026-10-15 09:30:00', 'Carga de trabajo en el período solicitado.', 2, '2026-10-12 11:00:00', '2026-10-15 09:30:00'),
(4, 4, 'sick', '2026-10-19', '2026-10-20', 'Reposo indicado por el médico.', 'approved', 2, '2026-10-18 16:00:00', 'Permiso aprobado.', 2, '2026-10-18 15:00:00', '2026-10-18 16:00:00');

-- ---------------------------------------------------------------------------
-- Vacantes
-- ---------------------------------------------------------------------------
INSERT INTO vacancies (id, title, description, department_id, position_id, opening_date, closing_date, status, created_by, updated_by, created_at, updated_at) VALUES
(1, 'Desarrollador Backend', 'Desarrollo de servicios y APIs internas.', 3, 4, '2026-10-01', NULL, 'open', 2, 2, '2026-10-01 09:00:00', '2026-10-01 09:00:00'),
(2, 'Analista de RR. HH.', 'Apoyo en procesos de gestión humana.', 2, 3, '2026-09-15', '2026-10-10', 'closed', 2, 2, '2026-09-15 09:00:00', '2026-10-10 18:00:00'),
(3, 'Ejecutivo de Ventas', 'Gestión comercial y atención a clientes.', 4, 6, '2026-10-05', NULL, 'open', 2, 2, '2026-10-05 09:00:00', '2026-10-05 09:00:00');

-- ---------------------------------------------------------------------------
-- Candidatos
-- ---------------------------------------------------------------------------
INSERT INTO candidates (id, first_name, last_name, email, phone, notes, created_by, updated_by, created_at, updated_at) VALUES
(1, 'Juan', 'Pérez', 'juan.perez@example.test', '555-0201', 'Candidato para desarrollo backend.', 2, 2, '2026-10-02 10:00:00', '2026-10-02 10:00:00'),
(2, 'María', 'López', 'maria.lopez@example.test', '555-0202', 'Experiencia en análisis de datos.', 2, 2, '2026-10-03 10:00:00', '2026-10-03 10:00:00'),
(3, 'Pedro', 'Sánchez', 'pedro.sanchez@example.test', '555-0203', 'Candidato para RR. HH.', 2, 2, '2026-09-18 10:00:00', '2026-09-18 10:00:00'),
(4, 'Lucía', 'Fernández', 'lucia.fernandez@example.test', '555-0204', 'Candidata seleccionada para análisis.', 2, 2, '2026-09-20 10:00:00', '2026-09-28 12:00:00'),
(5, 'Diego', 'Ramírez', 'diego.ramirez@example.test', '555-0205', 'Candidato para ventas.', 2, 2, '2026-10-06 10:00:00', '2026-10-06 10:00:00');

-- ---------------------------------------------------------------------------
-- Postulaciones
-- ---------------------------------------------------------------------------
INSERT INTO applications (id, candidate_id, vacancy_id, applied_at, status, notes, created_by, updated_by, created_at, updated_at) VALUES
(1, 1, 1, '2026-10-03 11:00:00', 'received', 'Postulación recibida.', 2, 2, '2026-10-03 11:00:00', '2026-10-03 11:00:00'),
(2, 2, 1, '2026-10-04 11:00:00', 'screening', 'Revisión inicial en curso.', 2, 2, '2026-10-04 11:00:00', '2026-10-05 09:00:00'),
(3, 3, 2, '2026-09-20 11:00:00', 'interview', 'Entrevista programada.', 2, 2, '2026-09-20 11:00:00', '2026-09-25 09:00:00'),
(4, 4, 2, '2026-09-28 11:00:00', 'selected', 'Candidata seleccionada; pendiente alta como empleada.', 2, 2, '2026-09-28 11:00:00', '2026-10-01 09:00:00'),
(5, 5, 3, '2026-10-06 11:00:00', 'received', 'Postulación recibida.', 2, 2, '2026-10-06 11:00:00', '2026-10-06 11:00:00'),
(6, 1, 3, '2026-10-07 11:00:00', 'rejected', 'Perfil no ajustado al cargo comercial.', 2, 2, '2026-10-07 11:00:00', '2026-10-08 09:00:00');

-- ---------------------------------------------------------------------------
-- Auditoría demo
-- ---------------------------------------------------------------------------
INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, metadata, ip_address, created_at) VALUES
(1, 1, 'user.login', 'user', 1, '{"role":"admin"}', '127.0.0.1', '2026-10-24 08:00:00'),
(2, 2, 'employee.deactivated', 'employee', 6, '{"reason":"demo"}', '127.0.0.1', '2026-10-10 09:00:00'),
(3, 2, 'leave.approved', 'leave_request', 2, '{"status":"approved"}', '127.0.0.1', '2026-10-10 10:00:00'),
(4, 2, 'vacancy.closed', 'vacancy', 2, '{"status":"closed"}', '127.0.0.1', '2026-10-10 18:00:00'),
(5, 2, 'application.selected', 'application', 4, '{"status":"selected"}', '127.0.0.1', '2026-10-01 09:00:00');
