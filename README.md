# GetnetArgentina

- Version de plugins descargables listos para ejecutar en comercios productivos y de UAT.

## Intrucciones al copiar modulo

1.  De la carpeta root de Magento, entrar a la ruta app/code, si no existe esta ultima se deberá crear.
2.  Dentro de ahi descomprimir el ZIP proporcionado, debera quedar la ruta app/code/GetnetArg

##  Como instalar el Modulo:

1.  Entra a la ruta inicial de tu instalacion de Magento (root folder)
2.  Ya dentro es necesario ejecutar los siguientes comandos uno por uno.

	php bin/magento cache:disable
	php bin/magento setup:upgrade
	
	php bin/magento setup:di:compile
	php bin/magento cache:enable


##  Configurar el modulo de Pago
1.  Ir a la opcion Stores/Configuration/Sales/Payment Methods
	Ya debera aparecer como opcion de pago Getnet Argentina
2.  Ingresar las credenciales que fueron otorgadas por el asesor de Getnet.


Versión compatible para Magento 2.3 y 2.4
