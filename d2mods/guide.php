<div class="page-header">
    <h2>Guide for Adding Stats to your Mods
        <small>BETA</small>
    </h2>
</div>

<p>This guide is still a Work-In-Progress, so check back later.</p>

<div class="alert alert-danger" role="alert"><strong>Special thanks to:</strong> <a href="https://github.com/SinZ163/"
                                                                                    target="_blank">SinZ163</a>, <a
        href="https://github.com/tetl/" target="_blank">Tet</a>, and <a href="https://github.com/ash47/"
                                                                        target="_blank">Ash47</a> for their hard-work in
    testing and developing the Lua and Flash code that makes this all possible. I take no credit for the Lua and Flash
    found here, as I have just collated and reformatted it.
</div>

<p>The barebones example repo can be found here: <a href="https://github.com/GetDotaStats/stat-collection/"
                                                    target="_blank">GetDotaStats/stat-collection</a>. All of the
    required libraries and example code is in there. Implementation simply involves splicing the statcollection logic
    into your mod.</p>

<p>Initial experimentation has revealed that via a combination of Flash and LUA, we can open socket connections with
    remote servers. We plan to take advantage of this by opening a socket back to our servers at the end of each game
    for stat gathering purposes. Before starting this guide, please ensure that you have added your mod to our
    directory. You will be provided with an encryption key that will be required towards the end of the guide.</p>

<h3>Data Schema</h3>

<p>Via Flash and LUA, you will communicate the following in JSON. Statistics that are "req" must be in the schema.
    Statistics that are "auto" are handled by the library automatically.</p>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Req</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>matchID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>98426ea5f41590</td>
            <td>Unique repeatable hash that will be repeatable for all of the clients in the same game (i.e. MD5 hash of
                modID, serverAddress, serverPort, and dateEnded)
            </td>
        </tr>
        <tr>
            <td>modID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>4d710f4c81bf6402e5</td>
            <td>Unique modID <a class="nav-clickable" href="#d2mods__my_mods" target="_blank">assigned to your
                    mod</a></td>
        </tr>
        <tr>
            <td>modes</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>ctf, 1v1, best100, best20</td>
            <td>Array of modes (even if only one mode selected)</td>
        </tr>
        <tr>
            <td>version</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>2.0.12</td>
            <td>Version of the mod</td>
        </tr>
        <tr>
            <td>duration</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>1234</td>
            <td>Duration of the game in seconds</td>
        </tr>
        <tr>
            <td>winner</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>Winning Team ID</td>
        </tr>
        <tr>
            <td>numTeams</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>Number of teams playing (in preparation of multi-team support getting added)</td>
        </tr>
        <tr>
            <td>numPlayers</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>8</td>
            <td>Number of players in game (important this is set as it effects if games are counted for stats)</td>
        </tr>
        <tr>
            <td>serverAddress</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>8</td>
            <td>Server address including port</td>
        </tr>
        <tr>
            <td>dateEnded</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>1409461194</td>
            <td>Match ending time as a Unix Timestamp</td>
        </tr>
        <tr>
            <td>playerInfo</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>playerInfo</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>rounds</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>rounds</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>playerInfo</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Req</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>teamID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>2</td>
            <td>Player's team ID (will obviously be 2 or 3 for now)</td>
        </tr>
        <tr>
            <td>slotID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>3</td>
            <td>Player's slot ID in their team</td>
        </tr>
        <tr>
            <td>playerName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>ᅠ<┼jiæ░d▒r▓y┼ ҉҈ᅠ</td>
            <td>Steam persona name of the player</td>
        </tr>
        <tr>
            <td>steamID32</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>28755155</td>
            <td>Player's steam account ID (same as Dotabuff's)</td>
        </tr>
        <tr>
            <td>steamID64</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>76561197989020883</td>
            <td>Player's steam ID (starts with 765)</td>
        </tr>
        <tr>
            <td>leaverStatus</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>4</td>
            <td>As recorded at end of the game. 0 = none, 1 = disconnected, 2 = disconnected timeout, 3 = abandoned
                match, 4 = AFK (no xp for 5mins), 5 = never connected, 6 = never connected too long (reached the
                timeout) (<a
                    href="https://github.com/SteamRE/SteamKit/blob/master/Resources/Protobufs/dota/dota_gcmessages_common.proto#L544"
                    target="_blank">refer to enum DOTALeaverStatus_t</a>)
            </td>
        </tr>
    </table>
</div>

<h4>rounds</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Req</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>winner</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>Winning team of the round (fill this even if you only have a single round)</td>
        </tr>
        <tr>
            <td>duration</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>1234</td>
            <td>Duration of the round in seconds</td>
        </tr>
        <tr>
            <td>players</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>playerInfo</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>players</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Req</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>teamID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>2</td>
            <td>Player's team ID (will obviously be 2 or 3 for now)</td>
        </tr>
        <tr>
            <td>slotID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>3</td>
            <td>Player's slot ID in their team</td>
        </tr>
        <tr>
            <td>steamID64</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>76561197989020883</td>
            <td>Player's steam ID (starts with 765)</td>
        </tr>
        <tr>
            <td>hero</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>heroInfo</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>items</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>items</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>abilities</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>abilities</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>hero</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Req</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>heroID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>100</td>
            <td>Hero ID of the player</td>
        </tr>
        <tr>
            <td>level</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>25</td>
            <td>Level of the player</td>
        </tr>
        <tr>
            <td>structureDamage</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>10203</td>
            <td>Damage player has done to structures</td>
        </tr>
        <tr>
            <td>heroDamage</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>39234</td>
            <td>Damage player has done to other players</td>
        </tr>
        <tr>
            <td>kills</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>13</td>
            <td>Kills player has performed</td>
        </tr>
        <tr>
            <td>assists</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>24</td>
            <td>Kills player has assisted with</td>
        </tr>
        <tr>
            <td>deaths</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>2</td>
            <td>Deaths player has accrued</td>
        </tr>
        <tr>
            <td>gold</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>734</td>
            <td>Deaths player has accrued</td>
        </tr>
        <tr>
            <td>denies</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>20</td>
            <td>Deaths player has accrued</td>
        </tr>
        <tr>
            <td>lastHits</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>100</td>
            <td>Deaths player has accrued</td>
        </tr>
    </table>
</div>

<h4>items</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Req</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>itemID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>255</td>
            <td>Item ID</td>
        </tr>
        <tr>
            <td>itemName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>item_testmod_wand_wizard</td>
            <td>Name of item (Unlocalised string)</td>
        </tr>
        <tr>
            <td>obtainStatus</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>0 = Purchased, 1 = Picked up, 2 = Given by ally</td>
        </tr>
        <tr>
            <td>lostStatus</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>0 = Sold, 1 = Dropped (including transferred to stash or ally), 2 = Used</td>
        </tr>
        <tr>
            <td>itemStartTime</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>123</td>
            <td>Number of seconds after round began that item was obtained</td>
        </tr>
        <tr>
            <td>itemEndTime</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>255</td>
            <td>Number of seconds after round began that item was used/lost</td>
        </tr>
    </table>
</div>

<h4>abilities</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Req</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>abilityID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>231</td>
            <td>Ability ID</td>
        </tr>
        <tr>
            <td>abilityName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>phantom_lancer_doppelwalk</td>
            <td>Name of ability (Unlocalised string)</td>
        </tr>
        <tr>
            <td>level</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>5</td>
            <td>Level of ability</td>
        </tr>
    </table>
</div>

<p>You will first need to implement your Flash and LUA methods for gathering the above data. Failing to collect all of
    the required data may result in your mod getting de-listed, or stats not functioning correctly. Below is a sample
    JSON to demonstrate the kind of string we are expecting:</p>

<pre class="pre-scrollable">
{"matchID" : 123123123123, "modID" : "abcdabcdabcd", "modes" : {0 : "ar", 1 : "dr"}, "version" : 0.1.23, "duration" : 123, "winner" : 1, "numTeams" : 2, "numPlayers" : 10, "autoSurrender" : 0, "massDisconnect" : 0, "serverAddress" : "192.168.0.1:27001", "dateEnded" : 123123123123}
</pre>

<p>There is standard "cookie cutter" code available in the "scripts/vscripts/lib/statcollection.lua" that should work
    for every mod. It is called in the form of getPlayerSnapshot() when the match ends. If your mod has multiple rounds,
    then you will need to modify that library such that you call it at the end of each round. All of the other data in
    the schema will need to be manually added during game play by calling addStats() with your array of stats.</p>

<h3>Setting up stat collection</h3>

<h4>Include the <strong><em>compiled</em></strong> flash code for sending data in your "resource/flash3" folder - <a
        href="https://github.com/GetDotaStats/stat-collection/raw/master/statcollection/resource/flash3/StatsCollection.swf"
        target="_blank">GitHub</a>
</h4>

<pre class="pre-scrollable">
    package  {
        import flash.display.MovieClip;
        import flash.net.Socket;
        import flash.utils.ByteArray;
        import flash.events.Event;
        import flash.events.ProgressEvent;
        import flash.events.IOErrorEvent;
        import flash.utils.Timer;
        import flash.events.TimerEvent;

        public class StatsCollection extends MovieClip {
            public var gameAPI:Object;
            public var globals:Object;
            public var elementName:String;

            var sock:Socket;
            var json:String;

            var SERVER_ADDRESS:String = "176.31.182.87";
            var SERVER_PORT:Number = 4444;

            public function onLoaded() : void {
                // Tell the user what is going on
                trace("##Loading StatsCollection...");

                // Reset our json
                json = '';

                // Load KV
                var settings = globals.GameInterface.LoadKVFile('scripts/stat_collection.kv');

                // Load the live setting
                var live:Boolean = (settings.live == "1");

                // Load the settings for the given mode
                if(live) {
                    // Load live settings
                    SERVER_ADDRESS = settings.SERVER_ADDRESS_LIVE;
                    SERVER_PORT = parseInt(settings.SERVER_PORT_LIVE);

                    // Tell the user it's live mode
                    trace("StatsCollection is set to LIVE mode.");
                } else {
                    // Load live settings
                    SERVER_ADDRESS = settings.SERVER_ADDRESS_TEST;
                    SERVER_PORT = parseInt(settings.SERVER_PORT_TEST);

                    // Tell the user it's test mode
                    trace("StatsCollection is set to TEST mode.");
                }

                // Log the server
                trace("Server was set to "+SERVER_ADDRESS+":"+SERVER_PORT);

                // Hook the stat collection event
                gameAPI.SubscribeToGameEvent("stat_collection_part", this.statCollectPart);
                gameAPI.SubscribeToGameEvent("stat_collection_send", this.statCollectSend);
            }
            public function socketConnect(e:Event) {
                // We have connected successfully!
                trace('Connected to the server!');

                // Hook the data connection
                //sock.addEventListener(ProgressEvent.SOCKET_DATA, socketData);
                var buff:ByteArray = new ByteArray();
                writeString(buff, json + '\r\n');
                sock.writeBytes(buff, 0, buff.length);
                sock.flush();
            }
            private static function writeString(buff:ByteArray, write:String){
                trace("Message: "+write);
                trace("Length: "+write.length);
                buff.writeUTFBytes(write);
            }
            public function statCollectPart(args:Object) {
                // Tell the client
                trace("##STATS Part of that stat data recieved:");
                trace(args.data);

                // Store the extra data
                json = json + args.data;
            }
            public function statCollectSend(args:Object) {
                // Tell the client
                trace("##STATS Sending payload:");
                trace(json);

                // Create the socket
                sock = new Socket();
                sock.timeout = 10000; //10 seconds is fair..
                // Setup socket event handlers
                sock.addEventListener(Event.CONNECT, socketConnect);

                try {
                    // Connect
                    sock.connect(SERVER_ADDRESS, SERVER_PORT);
                } catch (e:Error) {
                    // Oh shit, there was an error
                    trace("##STATS Failed to connect!");

                    // Return failure
                    return false;
                }
            }
        }
    }
</pre>

<h4>Call the compiled flash in your "resource/flash3/custom_ui.txt" - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/resource/flash3/custom_ui.txt"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    "CustomUI"
    {
        "1"
        {
            "File"      "XXXXXXX" //YOUR MAIN UI ELEMENT
            "Depth"     "253"
        }
        "2"
        {
            "File" "StatsCollection"
            "Depth" "1"
        }
    }
</pre>

<h4>Create a custom event in your "scripts/custom_events.txt" - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/custom_events.txt"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    "CustomEvents"
    {
        // Stat collection
        "stat_collection"
        {
            "json"          "string"
        }
    }
</pre>

<h4>Create a KV in your "scripts/stat_collection.kv" to make your stats test or live - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/stat_collection.kv"
        target="_blank">GitHub</a></h4>

<p>Ensure that it is set to live = 0. After the tests are successful, you will come back and update this file.</p>

<pre class="pre-scrollable">
    "Settings" {
        // Set to 1 for live, or 0 for test
        "live"                  "0"

        // Test Settings
        "SERVER_ADDRESS_TEST"   "176.31.182.87"
        "SERVER_PORT_TEST"      "4444"

        // Live Settings
        "SERVER_ADDRESS_LIVE"   "176.31.182.87"
        "SERVER_PORT_LIVE"      "4445"
    }
</pre>

<h4>Record the modID at the start of the game - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/vscripts/addon_game_mode.lua"
        target="_blank">GitHub</a></h4>

<p>It is important that you record the correct modID, otherwise your stats will not be recorded against your mod.
    Re-usage of modID between mods is not allowed, as it will invalidate both your stats and the original mod's
    stats. You can get your modID from your <a class="nav-clickable" href="#d2mods__my_mods" target="_blank">mod
        listing</a>.</p>

<pre class="pre-scrollable">
    -- Load Stat collection (statcollection should be available from any script scope)
    require('lib.statcollection')
    statcollection.addStats({
        modID = 'XXXXXXXXXXXXXXXXXXX' --GET THIS FROM http://getdotastats.com/#d2mods__my_mods
    })

    print( "Example stat collection game mode loaded." )
</pre>

<h4>Send the stats at the end of the game - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/vscripts/addon_game_mode.lua"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    function YourGamemode:GameThink()
        -- Check to see if the game has finished
        if GameRules:State_Get() >= DOTA_GAMERULES_STATE_POST_GAME then
            -- Send stats
            statcollection.sendStats()

            -- Delete the thinker
            return
        else
            -- Check again in 1 second
            return 1
        end
    end
</pre>

<h4>Finally!</h4>

<p>Now that you have the code implemented to collect and send stats, why not test it out? You can monitor what test data
    we receive via our logs
    <a href="./d2mods/log-test.html" target="_blank">test</a>
    ||
    <a href="./d2mods/log-live.html" target="_blank">live</a>.
    By default, you will be submitting to the test server. When you modify your "scripts/stat_collection.kv" by setting
    live = 1, your stats will appear in the live log and be elligible for recording. Only stats from approved mods will
    be accepted on the live server. Live stats that are sucessfully parsed will be recorded in our
    <a href="./d2mods/list_messages.php" target="_blank">database</a> (need to be logged in to view).
</p>

<h3>Understanding how the stat collection works</h3>

<p>Have a look at the <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/vscripts/lib/statcollection.lua"
        target="_blank">statcollection library</a>.
    This library handles the data you collect, and even abstracts the process for sending the stats from the rest of
    your logic.</p>

<h3>Custom Flash to send JSON</h3>

<p>If you want to understand what the compiled Flash is doing (or make your own), it essentially just opens a socket
    connection to 176.31.182.87 on port 4444 (for testing) OR 4445 (live) and sends the JSON string.</p>

<h3>Final steps</h3>

<p>You are now ready to go! Upload your mod to the workshop and see if it works! This method of stat collection is new
    and experimental, so feel free to contact me via <a
        href="http://github.com/GetDotaStats/site/issues" target="_new">Github Issues</a> / <a
        href="http://steamcommunity.com/id/jimmydorry/" target="_new">Steam</a> / <a
        href="irc://irc.gamesurge.net:6667/#getdotastats" target="_new">IRC</a> / <a
        href="http://chatwing.com/getdotaenterprises" target="_new">Site Chatbox</a> (on the right). <strong>If
        contacting me via Steam, make sure to leave a message on my profile, as I will likely not add you
        otherwise.</strong></p>

<h3>Miscellaneous Notes</h3>

<ul>
    <li>Do not re-use IDs for abilities, items, etc. If you remove an item from the game, and later add another, it is
        important that you do not re-use an existing ID as this will break the integrity of your stats database.
    </li>
</ul>