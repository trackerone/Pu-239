/*
 * AJAX Chat client-side configuration
 */

// CLass config
var UC_USER = 0;
var UC_POWER_USER = 1;
var UC_VIP = 2;
var UC_UPLOADER = 3;
var UC_MODERATOR = 4;
var UC_STAFF = 4;
var UC_ADMINISTRATOR = 5;
var UC_SYSOP = 6;
var ChatBot = 100;

// Ajax Chat config parameters:
var ajaxChatConfig = {

    // The channelID of the channel to enter on login (the loginChannelName is used if set to null):
    loginChannelID: null,
    // The channelName of the channel to enter on login (the default channel is used if set to null):
    loginChannelName: null,

    // The time in ms between update calls to retrieve new chat messages:
    timerRate: 2000,

    // The URL to retrieve the XML chat messages (must at least contain one parameter):
    ajaxURL: './?ajax=true',
    // The base URL of the chat directory, used to retrieve media files (images, sound files, etc.):
    baseURL: './',

    // A regular expression for allowed source URL's for media content (e.g. images displayed inline);
    regExpMediaUrl: '^((http)|(https)):\\/\\/',

    // If set to false the chat update is delayed until the event defined in ajaxChat.setStartChatHandler():
    startChatOnLoad: true,

    // Defines the IDs of DOM nodes accessed by the chat:
    domIDs: {
        // The ID of the chat messages list:
        chatList: 'chatList',
        // The ID of the online users list:
        onlineList: 'onlineList',
        // The ID of the message text input field:
        inputField: 'inputField',
        // The ID of the message text length counter:
        messageLengthCounter: 'messageLengthCounter',
        // The ID of the channel selection:
        channelSelection: 'channelSelection',
        // The ID of the style selection:
        styleSelection: 'styleSelection',
        // The ID of the emoticons container:
        emoticonsContainer: 'emoticonsContainer',
        // The ID of the color codes container:
        colorCodesContainer: 'colorCodesContainer',
        // The ID of the flash interface container:
        flashInterfaceContainer: 'flashInterfaceContainer',
        // The ID of the status icon:
        statusIcon: 'statusIconContainer'
    },

    // Defines the settings which can be modified by users:
    settings: {
        // Defines if BBCode tags are replaced with the associated HTML code tags:
        bbCode: true,
        // Defines if image BBCode is replaced with the associated image HTML code:
        bbCodeImages: true,
        // Defines if color BBCode is replaced with the associated color HTML code:
        bbCodeColors: true,
        // Defines if hyperlinks are made clickable:
        hyperLinks: true,
        // Defines if line breaks are enabled:
        lineBreaks: true,
        // Defines if emoticon codes are replaced with their associated images:
        emoticons: true,

        // Defines if the focus is automatically set to the input field on chat load or channel switch:
        autoFocus: false,
        // Defines if the chat list scrolls automatically to display the latest messages:
        autoScroll: true,
        // The maximum count of messages displayed in the chat list (will be ignored if set to 0):
        maxMessages: 0,

        // Defines if long words are wrapped to avoid vertical scrolling:
        wordWrap: true,
        // Defines the maximum length before a word gets wrapped:
        maxWordLength: 32,

        // Defines the format of the date and time displayed for each chat message:
        dateFormat: '[%H:%i:%s]',

        // Defines the format of the date displayed for each chat message tooltip:
        dateFormatTooltip: '%l, %F %d, %Y',

        // Defines if font colors persist without the need to assign them to each message:
        persistFontColor: false,
        // The default font color, uses the page default font color if set to null:
        fontColor: null,

        // Defines if sounds are played:
        audio: true,
        // Defines the audio backend:
        audioBackend: -1,
        // Defines the sound volume (0.0 = mute, 1.0 = max):
        audioVolume: 0.0,

        // Defines the sound that is played when normal messages are reveived:
        soundReceive: 'sound_1',
        // Defines the sound that is played on sending normal messages:
        soundSend: 'sound_2',
        // Defines the sound that is played on channel enter or login:
        soundEnter: 'sound_3',
        // Defines the sound that is played on channel leave or logout:
        soundLeave: 'sound_4',
        // Defines the sound that is played on chatBot messages:
        soundChatBot: 'sound_5',
        // Defines the sound that is played on error messages:
        soundError: 'sound_6',
        // Defines the sound that is played when private messages are received:
        soundPrivate: 'sound_7',

        // Defines if the document title blinks on new messages:
        blink: true,
        // Defines the blink interval in ms:
        blinkInterval: 500,
        // Defines the number of blink intervals:
        blinkIntervalNumber: 10
    },

    // Defines a list of settings which are not to be stored in a session cookie:
    nonPersistentSettings: [],

    // Defines the list of allowed BBCodes:
    bbCodeTags: [
        'b',
        'i',
        'u',
        'quote',
        'code',
        'color',
        'url',
        'img',
        'user',
        'power_user',
        'vip',
        'uploader',
        'moderator',
        'administartor',
        'sysop',
    ],

    // Defines the list of allowed color codes:
    colorCodes: [
        'gray',
        'silver',
        'white',
        'yellow',
        'orange',
        'red',
        'fuchsia',
        'purple',
        'navy',
        'blue',
        'aqua',
        'teal',
        'green',
        'lime',
        'olive',
        'maroon',
        'black'
    ],

    // Defines the list of allowed emoticon codes:
    emoticonCodes:[
        ":)",
        ":smile:",
        ":-D",
        ":lol:",
        ":w00t:",
        ":-P",
        ":blum:",
        ";-)",
        ":devil:",
        ":yawn:",
        ":-/",
        ":o)",
        ":innocent:",
        ":whistle:",
        ":unsure:",
        ":blush:",
        ":hmm:",
        ":hmmm:",
        ":huh:",
        ":look:",
        ":rolleyes:",
        ":kiss:",
        ":blink:",
        ":baby:",
        ":hi2:",
        ":pmsl:",
        ":-)",
        ":smile:",
        ":-D",
        ":lol:",
        ":w00t:",
        ":-P",
        ";-)",
        ":-|",
        ":-/",
        ":-(",
        ":'-(",
        ":weep:",
        ":-O",
        ":o)",
        "8-)",
        "|-)",
        ":innocent:",
        ":whistle:",
        ":closedeyes:",
        ":cool:",
        ":fun:",
        ":unsure:",
        ":thumbsup:",
        ":thumbsdown:",
        ":blush:",
        ":yes:",
        ":no:",
        ":love:",
        ":?:",
        ":!:",
        ":idea:",
        ":arrow:",
        ":hmm:",
        ":hmmm:",
        ":huh:",
        ":geek:",
        ":look:",
        ":rolleyes:",
        ":kiss:",
        ":shifty:",
        ":blink:",
        ":smartass:",
        ":sick:",
        ":crazy:",
        ":wacko:",
        ":alien:",
        ":wizard:",
        ":wave:",
        ":wavecry:",
        ":baby:",
        ":angry:",
        ":ras:",
        ":sly:",
        ":devil:",
        ":evil:",
        ":evilmad:",
        ":sneaky:",
        ":axe:",
        ":slap:",
        ":wall:",
        ":rant:",
        ":jump:",
        ":yucky:",
        ":nugget:",
        ":smart:",
        ":shutup:",
        ":shutup2:",
        ":crockett:",
        ":zorro:",
        ":snap:",
        ":beer:",
        ":beer2:",
        ":drunk:",
        ":strongbench:",
        ":weakbench:",
        ":dumbells:",
        ":music:",
        ":stupid:",
        ":dots:",
        ":offtopic:",
        ":spam:",
        ":oops:",
        ":lttd:",
        ":please:",
        ":sorry:",
        ":hi:",
        ":yay:",
        ":cake:",
        ":hbd:",
        ":band:",
        ":punk:",
        ":rofl:",
        ":bounce:",
        ":mbounce:",
        ":gathering:",
        ":hang:",
        ":chop:",
        ":horny:",
        ":rip:",
        ":whip:",
        ":judge:",
        ":chair:",
        ":tease:",
        ":box:",
        ":boxing:",
        ":guns:",
        ":shoot:",
        ":shoot2:",
        ":flowers:",
        ":wub:",
        ":lovers:",
        ":kissing:",
        ":kissing2:",
        ":console:",
        ":group:",
        ":hump:",
        ":hooray:",
        ":happy2:",
        ":clap:",
        ":clap2:",
        ":weirdo:",
        ":yawn:",
        ":bow:",
        ":dawgie:",
        ":cylon:",
        ":book:",
        ":fish:",
        ":mama:",
        ":pepsi:",
        ":medieval:",
        ":rambo:",
        ":ninja:",
        ":hannibal:",
        ":party:",
        ":snorkle:",
        ":evo:",
        ":king:",
        ":chef:",
        ":mario:",
        ":pope:",
        ":fez:",
        ":cap:",
        ":cowboy:",
        ":pirate:",
        ":pirate2:",
        ":rock:",
        ":cigar:",
        ":icecream:",
        ":oldtimer:",
        ":trampoline:",
        ":banana:",
        ":smurf:",
        ":yikes:",
        ":osama:",
        ":saddam:",
        ":santa:",
        ":indian:",
        ":pimp:",
        ":nuke:",
        ":jacko:",
        ":ike:",
        ":greedy:",
        ":super:",
        ":wolverine:",
        ":spidey:",
        ":spider:",
        ":bandana:",
        ":construction:",
        ":sheep:",
        ":police:",
        ":detective:",
        ":bike:",
        ":fishing:",
        ":clover:",
        ":horse:",
        ":shit:",
        ":soldiers:",
        ":)",
        ":wink:",
        ":D",
        ":P",
        ":(",
        ":'(",
        ":|",
        ":Boozer:",
        ":deadhorse:",
        ":spank:",
        ":yoji:",
        ":locked:",
        ":grrr:",
        "O:-",
        ":sleeping:",
        ":clown:",
        ":mml:",
        ":rtf:",
        ":dancer:",
        ":morepics:",
        ":rb:",
        ":rblocked:",
        ":maxlocked:",
        ":hslocked:",
        ":thankyou:",
        ":congrat:",
        ":thedevil:",
        ":drinks2:",
        ":rose:",
        ":good:",
        ":hi2:",
        ":pardon:",
        ":rofl2:",
        ":spite:",
        ":unknw:",
        ":cuppa:",
        ":smoken:",
        ":slick:",
        ":sun:",
        ":fart2:",
        ":lurker:",
        ":jawdrop:",
        ":sob:",
        ":whip2:",
        ":geek2:",
        ":madgrin:",
        ":byebye:",
        ":img:",
        ":alcohol:",
        ":pmsl:",
        ":bombie:",
        ":whoops:",
        ":banned:",
        ":faq:",
        ":iluvff:",
        ":starwars:",
        ":mage:",
        ":respect:",
        ":utorrent:",
        ":spliffy:",
        ":bear:",
        ":bandit:",
        ":congrats:",
        ":smokin:",
        ":canabis:",
        ":2gun:",
        ":bigun:",
        ":chainsaw:",
        ":drinks:",
        ":fight1:",
        ":fight2:",
        ":fight3:",
        ":fight4:",
        ":first:",
        ":Gotcha:",
        ":jumping:",
        ":yoda:",
        ":wink1:",
        ":upyours:",
        ":taz:",
        ":spew2:",
        ":spew:",
        ":sniper1:",
        ":smokie2:",
        ":sick2:",
        ":scream:",
        ":rasp2:",
        ":rasp:",
        ":party8:",
        ":party7:",
        ":party6:",
        ":party5:",
        ":party4:",
        ":party3:",
        ":party2:",
        ":party1:",
        ":oldman:",
        ":ninja2:",
        ":madaRse:",
        ":line:",
        ":last:",
        ":kenny:",
        ":jumping3:",
        ":jumping2:",
        ":jumping1:",
        ":pish:",
        ":grim:",
        ":taz2:",
        ":spiderman:",
        ":bong:",
        ":bat:",
        ":shotgun:",
        ":eye:",
        ":tumble:",
        ":welcome:",
        ":fart3:",
        ":caveman:",
        ":explode:",
        ":finger:",
        ":bhong:",
        ":bye:",
        ":slip:",
        ":jerry:",
        ":schair:",
        ":raver:",
        ":ras2:",
        ":moonie:",
        ":hides:",
        ":apache:",
        ":doobie:",
        ":acid:",
        ":angeldevil:",
        ":madraver:",
        ":clapper1:",
        ":high5:",
        ":shoutkiller:",
        ":bhong3:",
        ":bomb:",
        ":grey:",
        ":fart:",
        ":trumpet:",
        ":lmfao:",
        ":flmao:",
        ":googleit:",
        ":wow:",
        ":karma:",
        ":king2:",
        ":king3:",
        ":astronomer:",
        ":bluesbro:",
        ":Bunny:",
        ":cookies:",
        ":wired:",
        ":elektrik:",
        ":boom:",
        ":firebug:",
        ":fishy:",
        ":fishy2:",
        ":graffiti:",
        ":glue:",
        ":goodjob:",
        ":googleb:",
        ":grasshopper:",
        ":hint:",
        ":magnify:",
        ":mini2:",
        ":mini3:",
        ":mini4:",
        ":moo:",
        ":muhaha:",
        ":fubar:",
        ":nhlfan:",
        ":oldman2:",
        ":omg:",
        ":peacock:",
        ":pottymouth:",
        ":salute:",
        ":scuba2:",
        ":scythe:",
        ":shadowpet:",
        ":sharky:",
        ":sheesh:",
        ":smmdi:",
        ":boader:",
        ":soapbox1:",
        ":shappens:",
        ":swinger:",
        ":talk2:",
        ":usd:",
        ":wanted:",
        ":clowny:",
        ":angry_skull:",
        ":cheesy_skull:",
        ":cool_skull:",
        ":cry_skull:",
        ":embarassed_skull:",
        ":grin_skull:",
        ":huh_skull:",
        ":kiss_skull:",
        ":laugh_skull:",
        ":lipsrsealed_skull:",
        ":rolleyes_skull:",
        ":sad_skull:",
        ":shocked_skull:",
        ":smiley_skull:",
        ":tongue_skull:",
        ":undecided_skull:",
        ":wink_skull:",
        ":fart4:",
        ":Boozer:",
        ":deadhorse:",
        ":headbang:",
        ":bump:",
        ":spank:",
        ":yoji:",
        ":grrr:",
        ":mml:",
        ":rtf:",
        ":morepics:",
        ":rb:",
        ":rblocked:",
        ":maxlocked:",
        ":hslocked:",
        ":locked:",
        ":censoredpic:",
        ":dabunnies:"
    ],

    // Defines the list of emoticon files associated with the emoticon codes:
    emoticonFiles:[
        "smile1.gif",
        "smile2.gif",
        "grin.gif",
        "laugh.gif",
        "w00t.gif",
        "tongue.gif",
        "blum.gif",
        "wink.gif",
        "devil.gif",
        "yawn.gif",
        "confused.gif",
        "clown.gif",
        "innocent.gif",
        "whistle.gif",
        "unsure.gif",
        "blush.gif",
        "hmm.gif",
        "hmmm.gif",
        "huh.gif",
        "look.gif",
        "rolleyes.gif",
        "kiss.gif",
        "blink.gif",
        "baby.gif",
        "hi2.gif",
        "hysterical.gif",
        "smile1.gif",
        "smile2.gif",
        "grin.gif",
        "laugh.gif",
        "w00t.gif",
        "tongue.gif",
        "wink.gif",
        "noexpression.gif",
        "confused.gif",
        "sad.gif",
        "cry.gif",
        "weep.gif",
        "ohmy.gif",
        "clown.gif",
        "cool1.gif",
        "sleeping.gif",
        "innocent.gif",
        "whistle.gif",
        "closedeyes.gif",
        "cool2.gif",
        "fun.gif",
        "unsure.gif",
        "thumbsup.gif",
        "thumbsdown.gif",
        "blush.gif",
        "yes.gif",
        "no.gif",
        "love.gif",
        "question.gif",
        "excl.gif",
        "idea.gif",
        "arrow.gif",
        "hmm.gif",
        "hmmm.gif",
        "huh.gif",
        "geek.gif",
        "look.gif",
        "rolleyes.gif",
        "kiss.gif",
        "shifty.gif",
        "blink.gif",
        "smartass.gif",
        "sick.gif",
        "crazy.gif",
        "wacko.gif",
        "alien.gif",
        "wizard.gif",
        "wave.gif",
        "wavecry.gif",
        "baby.gif",
        "angry.gif",
        "ras.gif",
        "sly.gif",
        "devil.gif",
        "evil.gif",
        "evilmad.gif",
        "sneaky.gif",
        "axe.gif",
        "slap.gif",
        "wall.gif",
        "rant.gif",
        "jump.gif",
        "yucky.gif",
        "nugget.gif",
        "smart.gif",
        "shutup.gif",
        "shutup2.gif",
        "crockett.gif",
        "zorro.gif",
        "snap.gif",
        "beer.gif",
        "beer2.gif",
        "drunk.gif",
        "strongbench.gif",
        "weakbench.gif",
        "dumbells.gif",
        "music.gif",
        "stupid.gif",
        "dots.gif",
        "offtopic.gif",
        "spam.gif",
        "oops.gif",
        "lttd.gif",
        "please.gif",
        "sorry.gif",
        "hi.gif",
        "yay.gif",
        "cake.gif",
        "hbd.gif",
        "band.gif",
        "punk.gif",
        "rofl.gif",
        "bounce.gif",
        "mbounce.gif",
        "gathering.gif",
        "hang.gif",
        "chop.gif",
        "horny.gif",
        "rip.gif",
        "whip.gif",
        "judge.gif",
        "chair.gif",
        "tease.gif",
        "box.gif",
        "boxing.gif",
        "guns.gif",
        "shoot.gif",
        "shoot2.gif",
        "flowers.gif",
        "wub.gif",
        "lovers.gif",
        "kissing.gif",
        "kissing2.gif",
        "console.gif",
        "group.gif",
        "hump.gif",
        "hooray.gif",
        "happy2.gif",
        "clap.gif",
        "clap2.gif",
        "weirdo.gif",
        "yawn.gif",
        "bow.gif",
        "dawgie.gif",
        "cylon.gif",
        "book.gif",
        "fish.gif",
        "mama.gif",
        "pepsi.gif",
        "medieval.gif",
        "rambo.gif",
        "ninja.gif",
        "hannibal.gif",
        "party.gif",
        "snorkle.gif",
        "evo.gif",
        "king.gif",
        "chef.gif",
        "mario.gif",
        "pope.gif",
        "fez.gif",
        "cap.gif",
        "cowboy.gif",
        "pirate.gif",
        "pirate2.gif",
        "rock.gif",
        "cigar.gif",
        "icecream.gif",
        "oldtimer.gif",
        "trampoline.gif",
        "bananadance.gif",
        "smurf.gif",
        "yikes.gif",
        "osama.gif",
        "saddam.gif",
        "santa.gif",
        "indian.gif",
        "pimp.gif",
        "nuke.gif",
        "jacko.gif",
        "ike.gif",
        "greedy.gif",
        "super.gif",
        "wolverine.gif",
        "spidey.gif",
        "spider.gif",
        "bandana.gif",
        "construction.gif",
        "sheep.gif",
        "police.gif",
        "detective.gif",
        "bike.gif",
        "fishing.gif",
        "clover.gif",
        "horse.gif",
        "shit.gif",
        "soldiers.gif",
        "smile1.gif",
        "wink.gif",
        "grin.gif",
        "tongue.gif",
        "sad.gif",
        "cry.gif",
        "noexpression.gif",
        "alcoholic.gif",
        "deadhorse.gif",
        "spank.gif",
        "yoji.gif",
        "locked.gif",
        "angry.gif",
        "innocent.gif",
        "sleeping.gif",
        "clown.gif",
        "mml.gif",
        "rtf.gif",
        "dancer.gif",
        "morepics.gif",
        "rb.gif",
        "rblocked.gif",
        "maxlocked.gif",
        "hslocked.gif",
        "thankyou.gif",
        "clapping2.gif",
        "diablo.gif",
        "drinks2.gif",
        "give_rose.gif",
        "good.gif",
        "hi2.gif",
        "pardon.gif",
        "rofl2.gif",
        "spiteful.gif",
        "unknw.gif",
        "cuppa.gif",
        "smoke2.gif",
        "uber.gif",
        "read.gif",
        "fart2.gif",
        "lurker.gif",
        "jawdrop.gif",
        "sob.gif",
        "whip2.gif",
        "geek2.gif",
        "mad-grin.gif",
        "connie_mini_byebye.gif",
        "img.gif",
        "alcohol.gif",
        "hysterical.gif",
        "bomb_ie.gif",
        "whoops.gif",
        "banned.gif",
        "faq.gif",
        "iluvff.gif",
        "starwars.gif",
        "mage.gif",
        "respect.gif",
        "utorrent.gif",
        "spliffy.gif",
        "bear.gif",
        "bandit.gif",
        "congrats.gif",
        "smokin.gif",
        "canabis.gif",
        "2gun.gif",
        "biggun.gif",
        "chainsaw2.gif",
        "drinks.gif",
        "fight1.gif",
        "fight2.gif",
        "fight3.gif",
        "fight4.gif",
        "first.gif",
        "Gotcha.gif",
        "jumping.gif",
        "yoda.gif",
        "wink1.gif",
        "upyours.gif",
        "taz.gif",
        "spew2.gif",
        "spew.gif",
        "sniper1.gif",
        "smokie2.gif",
        "sick2.gif",
        "scream.gif",
        "rasp2.gif",
        "rasp.gif",
        "party8.gif",
        "party7.gif",
        "party6.gif",
        "party5.gif",
        "party4.gif",
        "party3.gif",
        "party2.gif",
        "party1.gif",
        "oldman.gif",
        "ninja2.gif",
        "madarse.gif",
        "Line.gif",
        "last.gif",
        "kenny.gif",
        "jumping3.gif",
        "jumping2.gif",
        "jumping1.gif",
        "pish.gif",
        "grim.gif",
        "taz2.gif",
        "spidey.gif",
        "bong.gif",
        "bat.gif",
        "shotgun.gif",
        "eye.gif",
        "tumbleweed.gif",
        "welcome.gif",
        "fart3.gif",
        "caveman.gif",
        "explode.gif",
        "finger.gif",
        "bhong.gif",
        "bye.gif",
        "slip.gif",
        "jerry.gif",
        "schair.gif",
        "raver.gif",
        "ras2.gif",
        "moonie.gif",
        "hides.gif",
        "apache.gif",
        "doobie.gif",
        "acid.gif",
        "angeldevil.gif",
        "madraver.gif",
        "clapper1.gif",
        "high5.gif",
        "shoutkiller.gif",
        "bhong3.gif",
        "bomb.gif",
        "grey.gif",
        "fart.gif",
        "trumpet.gif",
        "lmfao.gif",
        "lmao2.gif",
        "googleit.gif",
        "wow.gif",
        "karma.gif",
        "2nd.gif",
        "3rd.gif",
        "astronomer1.gif",
        "bluesbro.gif",
        "bunnywalk.gif",
        "cookies.gif",
        "electrician.gif",
        "elektrik.gif",
        "explosion.gif",
        "firebug.gif",
        "fishtale.gif",
        "fishy.gif",
        "graffiti.gif",
        "glueb.gif",
        "goodjob.gif",
        "googleb.gif",
        "grasshopper.gif",
        "hint.gif",
        "magglass.gif",
        "mini2.gif",
        "mini3.gif",
        "mini4.gif",
        "moo.gif",
        "muah.gif",
        "likefoobar.gif",
        "nhlfan.gif",
        "old.gif",
        "OMG_sign.gif",
        "peacock.gif",
        "pottymouth.gif",
        "salute3.gif",
        "scuba2.gif",
        "scythe.gif",
        "shadowpets.gif",
        "sharkycircle.gif",
        "sheesh.gif",
        "shemademedoit.gif",
        "skateboardb.gif",
        "soapbox1.gif",
        "stuffhappens.gif",
        "swinger1.gif",
        "talk2.gif",
        "usd.gif",
        "wanted.gif",
        "mindless.gif",
        "angry_skull.gif",
        "cheesy_skull.gif",
        "cool_skull.gif",
        "cry_skull.gif",
        "embarassed_skull.gif",
        "grin_skull.gif",
        "huh_skull.gif",
        "kiss_skull.gif",
        "laugh_skull.gif",
        "lipsrsealed_skull.gif",
        "rolleyes_skull.gif",
        "sad_skull.gif",
        "shocked_skull.gif",
        "smiley_skull.gif",
        "tongue_skull.gif",
        "undecided_skull.gif",
        "wink_skull.gif",
        "fart4.gif",
        "alcoholic.gif",
        "deadhorse.gif",
        "headbang.gif",
        "halo.gif",
        "spank.gif",
        "yoji.gif",
        "angry.gif",
        "mml.gif",
        "rtf.gif",
        "morepics.gif",
        "rb.gif",
        "rblocked.gif",
        "maxlocked.gif",
        "hslocked.gif",
        "locked.gif",
        "boucher-censored.jpg",
        "bunnies3.gif"
    ],

    emoticonDisplay:[
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        2,
        0
    ],


    // Defines the available sounds loaded on chat start:
    soundFiles: {
        sound_1: 'sound_1.mp3',
        sound_2: 'sound_2.mp3',
        sound_3: 'sound_3.mp3',
        sound_4: 'sound_4.mp3',
        sound_5: 'sound_5.mp3',
        sound_6: 'sound_6.mp3',
        sound_7: 'sound_7.mp3'
    },


    // Once users have been logged in, the following values are overridden by those in config.php.
    // You should set these to be the same as the ones in config.php to avoid confusion.

    // Session identification, used for style and setting cookies:
    sessionName: 'ajax_chat',
    // The time in days until the style and setting cookies expire:
    cookieExpiration: 365,
    // The path of the cookies, '/' allows to read the cookies from all directories:
    cookiePath: '/',
    // The domain of the cookies, defaults to the hostname of the server if set to null:
    cookieDomain: null,
    // If enabled, cookies must be sent over secure (SSL/TLS encrypted) connections:
    cookieSecure: null,
    // The name of the chat bot:
    chatBotName: 'ChatBot',
    // The userID of the chat bot:
    chatBotID: 2,
    // Allow/Disallow registered users to delete their own messages:
    allowUserMessageDelete: true,
    // Minutes until a user is declared inactive (last status update) - the minimum is 2 minutes:
    inactiveTimeout: 2,
    // UserID plus this value are private channels (this is also the max userID and max channelID):
    privateChannelDiff: 500000000,
    // UserID plus this value are used for private messages:
    privateMessageDiff: 1000000000,
    // Defines if login/logout and channel enter/leave are displayed:
    showChannelMessages: true,
    // Max messageText length:
    messageTextMaxLength: 1040,
    // Defines if the socket server is enabled:
    socketServerEnabled: false,
    // Defines the hostname of the socket server used to connect from client side:
    socketServerHost: 'localhost',
    // Defines the port of the socket server:
    socketServerPort: 1935,
    // This ID can be used to distinguish between different chat installations using the same socket server:
    socketServerChatID: 0,

    // Debug allows console logging or alerts on caught errors - false/0 = no debug, true/1/2 = console log, 2 = alerts
    debug: false
};
