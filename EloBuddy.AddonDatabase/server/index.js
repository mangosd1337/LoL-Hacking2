// server/index.js @flow
'use strict';
const PORT = process.env.PORT || 9000;
var express = require('express');
var morgan = require('morgan');
var path = require('path');
var mongoose = require('mongoose');
var app = express();

//mongodb
mongoose.connect('mongodb://localhost/addon-db');
mongoose.connection.on('error', function(e) {
  console.info('Mongoose default connection error: ' + e);
});
mongoose.connection.on('open', function() {
  console.info('Mongoose default connection opened');
});
mongoose.connection.on('disconnected', function () {
  console.log('Mongoose default connection disconnected');
});

// Setup logger
app.use(morgan(':remote-addr - :remote-user [:date[clf]] ":method :url HTTP/:http-version" :status :res[content-length] :response-time ms'));

// Serve static assets
app.use(express.static(path.resolve(__dirname, '..', 'build')));

// Always return the main index.html, so react-router render the route in the client
app.get('*', (req, res) => {
  res.sendFile(path.resolve(__dirname, '..', 'build', 'index.html'));
});

//auth route
app.use('api/auth', require('./routes/auth'));

var server = app.listen(PORT, () => {
  console.log(`App listening on port ${PORT}!`);
});


//Routine
/*
setInterval(function() {

}, 250);
*/

var UserDetail = new mongoose.Schema({
    username: String,
    password: String
}, {collection: 'userInfo'});

var UserDetails = mongoose.model('userInfo', UserDetail);

//auth
var passport = require('passport');
var LocalStrategy = require('passport-local').Strategy;

passport.use(new LocalStrategy(
  function(username, password, done) {
    process.nextTick(function () {
	   UserDetails.findOne({ 'username': username },
		  function(err, user) {
			 if (err) { return done(err); }
			 if (!user) { return done(null, false); }
			 if (user.password !== password) { return done(null, false); }
			 return done(null, user);
		  });
    });
  }
));
app.post('/login',
  passport.authenticate('local', {
    successRedirect: '/loginSuccess',
    failureRedirect: '/loginFailure'
  }));
