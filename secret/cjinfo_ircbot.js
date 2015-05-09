




var config = {
	channels: ["#joinmarket-pit"],
	server: "irc.snoonet.org",
	port: 6697,
	userName: 'cjtest',
    realName: 'cjtest',
	botName: "cjinfoTest",
	autoRejoin:true,
	secure: true
};



// Get the lib
var irc = require("irc");

// Create the bot name
var bot = new irc.Client(config.server, config.botName, config);

var 



bot.join("#joinmarket-pit",function (err) {
	console.log("joined");
});



bot.addListener("message", function(from, to, text, message) {
	console.log("got a message: "+from+"->"+to+": "+message);
	//bot.say(from, "¿Que?");
});


bot.addListener('error', function(message) {
    console.log('error: ', message);
});




