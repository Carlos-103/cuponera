1. ** Instalar WAMP**  
Descargar e instalar WAMP  
   
 Ejecutar como administrador  
2. ** Iniciar servicios**  
-Abrir WAMP y esperar que el icono esté en VERDE.  
Esto da a entender que Apache y MySQL estan funcionando  
-Si no inicia, cerrar cualquier otro proceso MySQL/XAMPP.  
 Es necesario que el puerto 3306 y el puerto 80 esten disponibles.  
3. ** Copiar el proyecto**  
Extraet el ZIP y copiar la carpeta a la siguiente ruta:  
C:\wamp64\www\  
4. **Importar la base de datos a phpmyadmin**  
-En el buscador colocar http://localhost/  
   
 -En la apartado de alias entrar en:  
   
 phpmyadmin 5.X.X(segun sea la version instalada)  
   
 -Dentro de phpMyAdmin:  
   
 -Click en el tab Importar:  
   
 -Seleccionar el archivo cuponera.sql  
   
 -Ejecutar la importación  
   
 -Verificar que todo este en verde  
   
   
   
   
5. **Verificar la conexión**  
Se tiene que verificar que este bien la conexion en database.php:  
define('DB_HOST', '127.0.0.1');  
   
 define('DB_PORT', '3306');  
   
 define('DB_USER', 'root');  
   
 define('DB_PASS', '');  
   
 define('DB_NAME', 'cuponera_sv');  
6. ** Abrir el sistema**  
http://localhost/cuponera/login.php  
7. **Credenciales**  
8. Administrador  
   
 Usuario: admin  
   
 Contraseña: password  
- Registar los siguientes usuarios:  
2. Empresa  
   
 Usuario: superselecots@gmail.com  
   
 Contraseña: 45_61*g9/  
   
 Usuario: superepuesto@gmail.com  
   
 Contraseña: 222..57#R  
   
3. Cliente  
   
 Usuario: Ramooon  
   
 Contraseña: 14786325  
