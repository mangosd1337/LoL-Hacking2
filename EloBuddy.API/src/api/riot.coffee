request = require 'request'
util    = require 'util'
fs      = require 'fs'
dl      = require 'download'
md5     = require 'md5-file'
zlib    = require 'zlib'
gzip    = zlib.createGzip();
require('buffertools').extend();

RELEASELISTING_NA       = 'http://l3cdn.riotgames.com/releases/live/projects/lol_game_client/releases/releaselisting_NA'
RELEASELISTING_PBE      = 'http://l3cdn.riotgames.com/releases/pbe/projects/lol_game_client/releases/releaselisting_PBE'

DL_NA                   = 'http://l3cdn.riotgames.com/releases/live/projects/lol_game_client/releases/%s/files/League%20of%20Legends.exe.compressed'
DL_PBE                  = 'http://l3cdn.riotgames.com/releases/pbe/projects/lol_game_client/releases/%s/files/League%20of%20Legends.exe.compressed'

class Riot
   @hashArray: [],
   @dlQueue: [],
   @isLocked: false,
   @version: null,

   constructor: (_versionInst) ->
      self = @

      Riot.version = _versionInst;

      Riot.hashArray['NA'] = []
      Riot.hashArray['PBE'] = []

      setInterval self.refresh, 60000
      this.refresh(true)

      setInterval self.dlWorker, 500
      setInterval self.updateIsUpdatedArray, 30000

   @releaseListingToType: (uri) ->
      if !!~uri.indexOf('pbe')
         return 'PBE'
      return 'NA'

   updateIsUpdatedArray: () ->
      types = [ 'NA', 'PBE' ]

      for type in types
         for i in [0 .. Riot.hashArray[type].length - 1]
            Riot.hashArray[type][i].updated = Riot.version.isUpdated(Riot.hashArray[type][i].hash)

   dlWorker: () ->
      if Riot.isLocked
         return false

      for uriObject in Riot.dlQueue
         endPoint = if uriObject.type == 'PBE' then DL_PBE else DL_NA

         uri = util.format(endPoint, uriObject.version)
         dir = "files/#{uriObject.type}/#{uriObject.version}"
         fullPath = "#{dir}/League%20of%20Legends.exe.compressed"
         fullPathUnpacked = "#{dir}/League of Legends.exe"

         if !fs.existsSync(fullPathUnpacked)
            console.log "Downloading #{uriObject.version}..."

            fs.mkdir(dir, () ->
               Riot.isLocked = true

               new dl()
                  .get(uri)
                  .dest("#{dir}/")
                  .run( () ->
                     Riot.isLocked = false
                     fs.readFile(fullPath, (err, buff) ->

                        zlib.unzip(buff, (err, buf2) ->
                           fs.writeFileSync(fullPathUnpacked, buf2)
                           Riot.computeMD5Hashes(fullPathUnpacked, uriObject.version, uriObject.type)
                        )
                     )
                  )
            )
         else
            console.log "Skipping download of '#{uriObject.version}' - reason: file exists"
            Riot.computeMD5Hashes(fullPathUnpacked, uriObject.version, uriObject.type)

         Riot.dlQueue.splice(0, 1)

         break;

   @computeMD5Hashes: (fullPath, version, type)  ->
      for i in [0 .. Riot.hashArray[type].length - 1]
         versionObject = Riot.hashArray[type][i]
         if versionObject.version == version && Riot.hashArray[type][i].hash.length != 32
            Riot.hash fullPath, type, i


   @hash: (fullPath, type, i) ->
      console.log "Hashing: Version: #{version} Type: #{type}..."

      fileBytes = fs.readFileSync fullPath

      version = ''
      buildDate = ''
      begin = fileBytes.indexOf('Releases') + "Releases".length + 1
      for k in [0..2]
         version += String.fromCharCode(fileBytes[k + begin])

      if !~version.indexOf('.')
         version = 'Unknown'

      md5 fullPath, (err, sum) ->
         if !err
               Riot.hashArray[type][i].prettyVersion = version.replace(/\s/g, '').replace('\u0000', '')
               Riot.hashArray[type][i].hash = sum
               Riot.hashArray[type][i].updated = Riot.version.isUpdated(Riot.hashArray[type][i].hash)


      console.info "Hash calculated... continuing"

   refresh: (initial) ->
      console.info 'Refreshing Riot Hash Arrays...'

      releaseListings = [ RELEASELISTING_NA, RELEASELISTING_PBE ]

      for releaseListing in releaseListings
         request releaseListing, (error, response, body) ->
            if !error && response.statusCode == 200
               versions = body.split('\r\n').slice(0, 5)

               for version in versions
                  versionExists = false
                  for cmpVersion in Riot.hashArray[Riot.releaseListingToType(response.request.href)]
                     if cmpVersion.version == version
                        versionExists = true

                  versionObject =
                     version: version,
                     prettyVersion: 'queued',
                     updated: false,
                     hash: 'queued'

                  # prepend
                  if !versionExists && !initial
                     Riot.hashArray[Riot.releaseListingToType(response.request.href)].unshift versionObject

                  # append
                  if !versionExists && initial
                     Riot.hashArray[Riot.releaseListingToType(response.request.href)].push versionObject

                  # add to dlQueue
                  if !versionExists
                     Riot.dlQueue.push
                        version: version,
                        type: Riot.releaseListingToType(response.request.href)

                  if !versionExists && !initial
                     console.info "Version #{version} added to #{Riot.releaseListingToType(response.request.href)}!"

   getFileList: (type) ->
      return Riot.hashArray[type].slice(0, 5)

   handle: (request, response) ->
      response.header 'Access-Control-Allow-Origin', '*'

      response.json({
            dlQueue: Riot.dlQueue.length,
            fileList: [
               na: this.getFileList('NA')
               pbe: this.getFileList('PBE')
            ]
      })

module.exports = Riot
