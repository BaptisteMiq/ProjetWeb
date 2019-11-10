/**
 * @file apirouter
 * @author EclipsioZ
 * @license GPL-3.0
 */

// Importation de la librairie express
const express = require('express');

// Importation de l'userController
const usersCtrl = require('./Route/userController.js');

// Router

exports.router = (function() {

    var apiRouter = express.Router();

    // User
    apiRouter.route('/users/register/').post(usersCtrl.register);
    apiRouter.route('/users/login/').post(usersCtrl.login);

    return apiRouter;
})();