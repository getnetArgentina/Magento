# GetnetArgentina

- Version del proyecto de Getnet para implementar el flujo de cobro en Magento 2.3 o 2.4

## Intrucciones al copiar modulo

1.  De la carpeta root de Magento, entrar a la ruta app/code, si no existe esta ultima se deberá crear.
2.  Dentro de esa ruta se debera crear la carpeta GetnetArg y dentro de ella crear tambien la carpeta Payments, dentro de esa carpeta deberan copiar el contenido de este proyecto,  quedando así la ruta app/code/GetnetArg/Payments

##  Como instalar el Modulo:

1.  Entra a la ruta inicial de tu instalacion de Magento (root folder) por consola (ssh)
2.  Es necesario jecutar los siguientes comandos uno por uno.

	php bin/magento cache:disable
	php bin/magento setup:upgrade
	
	php bin/magento setup:di:compile
	php bin/magento cache:enable


##  Configurar el modulo de Pago
1.  Ir a la opcion Stores/Configuration/Sales/Payment Methods
	Ya debera aparecer como opcion de pago Getnet by Santander
2.  Ingresar las credenciales que fueron otorgadas por el asesor de Getnet.
