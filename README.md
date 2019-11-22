## Aperçu

![](https://i.imgur.com/nKvoHnP.png)
![](https://i.imgur.com/1SgzXlM.jpg)
![](https://i.imgur.com/fL54NVM.png)
![](https://i.imgur.com/t4He2gT.png)

## Guise d'installation du projet

### Téléchargement & installation

git clone https://github.com/BaptisteMiq/ProjetWeb.git

cd ProjetWeb

composer update


### Configurations PHP pour la connexion à l'API

Télécharger le fichier https://curl.haxx.se/ca/cacert.pem

Dans php.ini, modifier (ou ajouter):

extension=php_openssl.dll

extension=php_curl.dll

curl.cainfo = "CHEMIN VERS PHP\cacert.pem"

(ex: curl.cainfo = "D:\Programmes\php\cacert.pem")
