/**
 * @file userController
 * @author EclipsioZ
 * @license GPL-3.0
 */

 // Importation de la librairie bcrypt
 const bcrypt = require('bcrypt');

 // Importation de la librairie jwt.utils
 const jwtUtils = require('../utils/jwt.utils.js')


 // Importation de la bdd
 const model = require('../models');

 // Définition de nos routes

 module.exports = {

    register: function(req, res){

        //Récupération des paramètres
        var email = req.body.email;
        var username = req.body.username;
        var password = req.body.password;

        console.log(req.body.username);

        if(email == "" || username == "" || password == "") {
            return res.status(400).json({'error': 'Paramètres manquants !'});
        }

        model.User.findOne({
            attributes: ['email'],
            where: {email: email}
        })
        .then(function(userFound){
            if(!userFound) {
                bcrypt.hash(password, 5, function(err, bcryptedPassword) {
                  var newUser = model.User.create({
                    email: email,
                    username: username,
                    password: bcryptedPassword,
                    isBDE: 0,
                    isPersonnel: 0
                  })
                  .then(function(newUser) {
                      return res.status(201).json({
                          'userId': newUser.id
                      })
                  })
                  .catch(function(err) {
                    return res.status(500).json({'error': 'Impossible d\'ajouter le nouveau utilisateur'});
                  });
                });

            } else {
                return res.status(409).json({'error': 'Utilisateur déjà  existant !'});
            }
        })
        .catch(function(err) {
            return res.status(500).json({ 'error': 'Impossible de vérifier l\'utilisateur !'});
        });

    },
    login: function(req, res){

        var email = req.body.email;
        var password = req.body.password;

        console.log(req);

        if (email == "" || password == ""){
            return res.status(400).json({'error': 'Paramètres manquants !'});
        }

        model.User.findOne({
            where: {email: email}
        })
        .then(function(userFound){      
            if(userFound) {
                bcrypt.compare(password, userFound.password, function(errBycrypt, resBycrypt) {

                    if(resBycrypt) {
                        return res.status(200).json({
                            'userId': userFound.id,
                            'token': jwtUtils.generateTokenForUser(userFound)
                        });
                    } else {
                        return res.status(403).json({"error": 'Le mot de passe rentré n\'est pas correct !'})
                    }
                });

            } else {
                return res.status(409).json({'error': 'Cette utilisateur n\'existe pas !'});
            }
        })
        .catch(function(err) {
            return res.status(500).json({ 'error': 'Impossible de vérifier l\'utilisateur !'});
        });
    }
 }