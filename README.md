# 🚚 El Ciruelo · Expediciones

Aplicación web desarrollada en **PHP** con **MySQL**, que permite a los **carretilleros** de la empresa **El Ciruelo** asignar palets a camiones dentro de una **orden de carga**.

---

## ⚙️ Cómo ejecutar el proyecto

1. Copia la carpeta del proyecto dentro de la ruta:

   ```
   C:\xampp\htdocs\
   ```

2. Inicia **XAMPP** y asegúrate de que los servicios **Apache** y **MySQL** estén activos.

3. Abre **phpMyAdmin** desde tu navegador:

   [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)

4. Crea una nueva base de datos (por ejemplo, `expediciones`).

5. Importa el archivo SQL que encontrarás en:

   ```
   /BBDD/sql
   ```

6. Abre el proyecto en el navegador:

   [http://localhost/expediciones/Views/login.php](http://localhost/expediciones/Views/login.php)

---

## 🗂️ Estructura del código

```
expediciones/
├── Controllers/
│   └── ExpedicionesController.php
│
├── Models/
│   ├── ConexionPDOModel.php
│   └── ExpedicionesModel.php
│
├── Views/
│   ├── login.php          ← Selección de carretillero
│   ├── index.php          ← Listado de órdenes de carga
│   ├── asignar.php        ← Asignación de palets a camiones
│   └── success.php        ← Confirmación de asignación
│
├── BBDD/
│   └── sql/               ← Script SQL con datos de prueba
│
└── README.md
```

---

## 🧠 Descripción rápida

- Los carretilleros inician sesión seleccionando su **nombre**.  
- Pueden consultar las **órdenes de carga abiertas o en curso**.  
- En cada orden se muestran los **camiones disponibles**.  
- Al introducir el **ID del palet** y seleccionar un camión:
  - Se valida si el palet existe.  
  - Se asigna y se cambia su estado a **ASIGNADO**.  
  - Si el camión se llena, la orden pasa automáticamente a **CERRADA**.  

---

## 💻 Tecnologías utilizadas

- **PHP 8**
- **MySQL**
- **HTML / CSS / Bootstrap**
- **JavaScript**
- **Arquitectura MVC**

---

## 👤 Autor

**Alberto Martínez Muñoz**
