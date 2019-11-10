## Guise d'installation du projet

### Téléchargement & installation

git clone https://github.com/BaptisteMiq/ProjetWeb.git

cd ProjetWeb

composer update


### Configurations PHP pour la connexion à l'API

Télécharger le fichier https://curl.haxx.se/ca/cacert.pem

Dans php.ini, modifier (ou ajouter):


extension=php_curl.dll

curl.cainfo = "CHEMIN VERS PHP\cacert.pem"

(ex: curl.cainfo = "D:\Programmes\php\cacert.pem")