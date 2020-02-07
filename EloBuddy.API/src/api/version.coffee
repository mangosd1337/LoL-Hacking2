request = require 'request'

DEPENDENCIES_JSON_URL = 'https://raw.githubusercontent.com/EloBuddy/EloBuddy.Dependencies/master/dependencies.json'

class Version
   @updatedVersions: [],

   constructor: ->
      setInterval this.refresh, 60000
      this.refresh()

   refresh: ->
      console.info 'Refreshing dependencies...'

      request DEPENDENCIES_JSON_URL, (error, response, body) ->
         if !error && response.statusCode == 200
            patches = JSON.parse(body)['Patches']

            Object.keys(patches).forEach (leagueHash) ->
               if !~Version.updatedVersions.indexOf(leagueHash)
                  Version.updatedVersions.push(leagueHash)

                  console.info "Added '#{leagueHash}' to updated hash list"

   isUpdated: (md5Hash) ->
      if md5Hash.length != 32
         return false

      return Version.updatedVersions.indexOf(md5Hash) != -1

   handle: (request, response) ->
      leagueHash = request.params[0]

      if leagueHash.length != 32
         return response.json
            status: 'success',
            updatedVersions: Version.updatedVersions


      response.json
         status: 'success',
         leagueHash: leagueHash,
         updated: this.isUpdated(leagueHash)
      

module.exports = Version
