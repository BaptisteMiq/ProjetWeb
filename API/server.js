/**
 * @file server
 * @author EclipsioZ
 * @license GPL-3.0
 */

// Importation de la librarie Express
const express = require('express');

// Importation de la librarie Body Parser
const bodyParser = require('body-parser');

// Importation de notre routeur
const apiRouter = require('./apiRouter.js').router;

// Déclaration de notre variable server qui prend la valeur de la fonction express()
var server = express();

// Configuration de body Parser
server.use(bodyParser.urlencoded({ extended: true}));
server.use(bodyParser.json());


// Cofiguration de notre routes
server.get('/', function(req, res) {

    res.setHeader('Content-Type', 'text/html');
    res.status(200).send('<h1> Site officiel du BDE Cesi </h1>');
});

server.use('/api/', apiRouter);

// Lancement de notre serveur
server.listen(666, function() {

console.log("Le serveur c'est bien lancé correctement !");

});