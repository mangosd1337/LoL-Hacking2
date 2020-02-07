lol               = require 'lol-js'

VALID_LOL_REGIONS = [ 'br', 'eune', 'euw', 'kr', 'lan', 'las', 'na', 'oce', 'ru', 'tr' ]
API_KEY           = 'ea19371b-19f6-40b0-8e4e-f423bf1bd606';

class Mastery
   constructor: ->

   error: (response, reason, summonerName, region) ->
   	response.json {
   		status: 'error',
   		summonerName: summonerName,
   		reason: reason
   		masteries: []
   	}

   handle: (request, response) ->
      region = request.params[0]
      summonerName = request.params[1]

      console.info "Request for summoner #{summonerName}"

      if !~VALID_LOL_REGIONS.indexOf(region)
         this.error(response, 'Invalid region', summonerName, region)
         console.error "Invalid region '#{region}' for summoner '#{summonerName}'"

      client = lol.client {
         apiKey: API_KEY,
         defaultRegion: region
      }

      client.getSummonersByNameAsync [ summonerName ]
      .then (summoners) ->
         summoner = summoners[summonerName]

         if summoner == null
            this.error(response, 'Can\'t find summoner', summonerName, region)
            console.error "Failed to find summoner #{summonerNmae}"

         console.info "Summoner '#{summoner.name}' has been assigned to summonerId '#{summoner.id}'"

         client.getSummonerMasteriesAsync [ summoner.id ]
         .then (masteries) ->
            masteryPages = masteries[summoner.id].pages
            mainMasteryPage = null

            for masteryPage in masteryPages
               if masteryPage.current
                  mainMasteryPage = masteryPage.masteries || []

            console.info "Masteries for summoner '#{summoner.name}' have been successfully parsed"

            response.json {
               status: 'success',
               summoner: summoner,
               masteries: mainMasteryPage || []
            }


module.exports = Mastery
