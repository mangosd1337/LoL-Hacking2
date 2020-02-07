express  = require 'express'
app      = express()
require('console-stamp')(console, {
   metadata:  ->
      return "[PID:#{process.pid}]"

   colors: {
      stamp: 'yellow',
      label: 'white',
      metadata: 'green'
   }
})

# CONFIG
APP_PORT          = 80

# API ROUTES
Mastery            = require './api/mastery'
Version            = require './api/version'
Riot               = require './api/riot'

_mastery = new Mastery
_version = new Version
_riot    = new Riot(_version)

# ROUTES

app.get '/masteries/*/*', (request, response) ->
   _mastery.handle(request, response)

app.get '/deploy/core/*', (request, response) ->
   _version.handle(request, response)

app.get '/riot/info*', (request, response) ->
   _riot.handle(request, response)

app.listen APP_PORT, () ->
   console.info "EloBuddy.API launched on port '#{APP_PORT}'"
