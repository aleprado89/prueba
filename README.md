# Sistema de Información Pedagógica para Escuelas

## Descripción del Proyecto

Este es un sistema de información pedagógica desarrollado en PHP puro para la gestión educativa de instituciones escolares. El sistema permite la autogestión de alumnos, docentes y personal administrativo (secretarios), facilitando la administración de calificaciones, inscripciones, exámenes y reportes académicos.

## Arquitectura del Sistema

### Subsistemas

El sistema está dividido en tres subsistemas principales:

1. **Autogestión de Alumnos** (`inicio/login.php`)
   - Acceso para estudiantes
   - Consulta de calificaciones y asistencias
   - Solicitudes de inscripción a materias y exámenes
   - Gestión de datos personales

2. **Autogestión de Docentes** (`inicio/login.php`)
   - Acceso para profesores
   - Carga de calificaciones y asistencias
   - Gestión de actas de examen
   - Consulta de materias asignadas

3. **Sistema Principal Administrativo** (`inicio/loginAdmin.php`)
   - Acceso para secretarios y personal administrativo
   - Gestión completa de alumnos, docentes y cursos
   - Administración de planes de estudio y materias
   - Generación de reportes y estadísticas

## Tecnologías Utilizadas

- **Backend**: PHP 7.1+ (puro, sin frameworks)
- **Base de Datos**: MySQL
- **Frontend**: HTML, CSS (Bootstrap + Bootswatch Materia), JavaScript (jQuery)
- **Reportes**: Dompdf para generación de PDFs
- **Estilos**: `css/estilos.css` + Bootswatch Materia

## Estructura del Proyecto

```
/
├── alumnos/          # Funcionalidades de autogestión de alumnos
├── docentes/         # Funcionalidades de autogestión de docentes
├── secretaria/       # Sistema administrativo principal
├── reportes/         # Generación de reportes en PDF
├── funciones/        # Utilidades y configuración
│   ├── consultas.php # TODAS las consultas a base de datos
│   ├── menu.php      # Sistema de menús
│   └── ...
├── inicio/           # Sistema de autenticación
├── css/              # Estilos CSS
├── js/               # JavaScript y jQuery
├── img/              # Imágenes y logos
└── vendor/           # Dependencias (Composer)
```

## Principios de Desarrollo

### Metodología de Programación

- **Encapsulamiento de BD**: Todas las consultas MySQL están centralizadas en `funciones/consultas.php`
- **Separación de Lógica**: Nunca se ejecutan consultas directas en archivos de presentación
- **Sanitización de Parámetros**: Enfoque estricto en seguridad y validación de inputs
- **Estética del Código**: Código legible, organizado y mantenible
- **Páginas Single-File**: Cada página de usuario es un archivo PHP único que incluye HTML

### Seguridad

- Uso de prepared statements para todas las consultas
- Validación y sanitización de todos los parámetros de entrada
- Control de sesiones y autenticación
- Prevención de inyección SQL y XSS

## Funcionalidades Principales

### Para Alumnos
- Consulta de calificaciones por materia y plan de estudio
- Visualización de asistencias
- Solicitudes de inscripción a materias y exámenes
- Actualización de datos personales

### Para Docentes
- Carga de calificaciones y asistencias
- Gestión de actas de examen
- Consulta de alumnos por materia
- Generación de reportes de asistencia

### Para Administración
- Gestión completa de alumnos y docentes
- Administración de planes de estudio y curricula
- Matriculación de alumnos en cursos y materias
- Sistema de equivalencias y convalidaciones
- Generación de reportes académicos
- Gestión de mesas de examen

## Instalación y Configuración

1. **Requisitos del Sistema**
   - PHP 7.1 o superior
   - MySQL 5.7+
   - Servidor web (Apache/Nginx)
   - Extensiones PHP: mysqli, mbstring, gd

2. **Instalación**
   
```bash
   # Clonar o descargar el proyecto
   # Configurar base de datos MySQL
   # Importar esquema de BD (no incluido en este repo)
   # Configurar conexión en inicio/conexion.php
   
```

3. **Configuración**
   - Editar `inicio/conexion.php` con credenciales de BD
   - Configurar rutas absolutas si es necesario
   - Verificar permisos de escritura en directorios de uploads

## Uso del Sistema

### Acceso al Sistema
- **Alumnos/Docentes**: `inicio/login.php`
- **Administración**: `inicio/loginAdmin.php`

### Navegación
Cada subsistema tiene su propio menú de navegación definido en `funciones/menu.php` con funciones específicas para cada rol.

## Desarrollo y Mantenimiento

### Buenas Prácticas
- Todas las consultas nuevas deben implementarse en `consultas.php`
- Mantener consistencia en el estilo de código
- Documentar funciones complejas
- Probar cambios en entorno de desarrollo antes de producción

### Estructura de Consultas
```php
function nombreFuncion($conexion, $param1, $param2) {
    $consulta = "SELECT/INSERT/UPDATE...";
    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("tipos", $param1, $param2);
    $stmt->execute();
    // Procesar resultados
    return $resultado;
}
```

## Contribución

Para contribuir al proyecto:
1. Seguir los principios de desarrollo establecidos
2. Mantener la estructura de consultas centralizadas
3. Priorizar seguridad y estética del código
4. Probar exhaustivamente antes de commits

## Licencia

Este proyecto es propiedad de la institución educativa. Consulta con el administrador del sistema para términos de uso.

## Soporte

Para soporte técnico o consultas sobre el sistema, contactar al equipo de desarrollo o al administrador del sistema educativo.
