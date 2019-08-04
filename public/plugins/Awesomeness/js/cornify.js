
/*
 
    _______      ,-----.    .-------.    ,---.   .--..-./`)  ________    ____     __  
   /   __  \   .'  .-,  '.  |  _ _   \   |    \  |  |\ .-.')|        |   \   \   /  / 
  | ,_/  \__) / ,-.|  \ _ \ | ( ' )  |   |  ,  \ |  |/ `-' \|   .----'    \  _. /  '  
,-./  )      ;  \  '_ /  | :|(_ o _) /   |  |\_ \|  | `-'`"`|  _|____      _( )_ .'   
\  '_ '`)    |  _`,/ \ _/  || (_,_).' __ |  _( )_\  | .---. |_( )_   | ___(_ o _)'    
 > (_)  )  __: (  '\_/ \   ;|  |\ \  |  || (_ o _)  | |   | (_ o._)__||   |(_,_)'     
(  .  .-'_/  )\ `"/  \  ) / |  | \ `'   /|  (_,_)\  | |   | |(_,_)    |   `-'  /      
 `-'`-'     /  '. \_/``".'  |  |  \    / |  |    |  | |   | |   |      \      /       
   `._____.'     '-----'    ''-'   `'-'  '--'    '--' '---' '---'       `-..-'        
                                                                                      

*/

var cornify_count = 0;
var cornify_add = function(options) {
	// Track how often we cornified.
	cornify_count += 1;

	var cornify_url = 'https://www.cornify.com/';
	
	// Create a container DIV for our 'corn or 'bow.
	var div = document.createElement('div');
	div.style.position = 'fixed';

	// Prepare our lovely variables.
	var numType = 'px';
	var heightRandom = Math.random() * 0.75;
	var windowHeight = 768;
	var windowWidth = 1024;
	var height = 0;
	var width = 0;
	var de = document.documentElement;

	// Get the window width and height - requires some cross browser checking.
	if(typeof(window.innerHeight) == 'number') {
		windowHeight = window.innerHeight;
		windowWidth = window.innerWidth;
	} else if(de && de.clientHeight) {
		windowHeight = de.clientHeight;
		windowWidth = de.clientWidth;
	} else {
		numType = '%';
		height = Math.round(height*100) + '%';
	}
	
	div.onclick = cornify_add; // Click for more magic.
	div.style.zIndex = 10;
	div.style.outline = 0;
	
	if(cornify_count == 15) {
		// Clicking 15 times summons the grand unicorn - which is centered on the screen.
		div.style.top = Math.max( 0, Math.round((windowHeight-530)/2))  + 'px';
		div.style.left = Math.round((windowWidth-530)/2) + 'px';
		div.style.zIndex = 1000;
	} else {
		// Otherwise we randomize the position.
		if(numType == 'px') {
			div.style.top = Math.round( windowHeight*heightRandom ) + numType;
		} else {
			div.style.top = height;
		}

		div.style.left = Math.round(Math.random()*90) + '%';
	}
	
	var img = document.createElement('img');
	var currentTime = new Date();

	// Used as a cache buster so the browser makes a new request every time instead of usign the previous, cached one.
	var submitTime = currentTime.getTime();

	if( cornify_count==15 ) submitTime = 0;

	// Construct our unicorn & rainbow request.
	var url = cornify_url+'getacorn.php?r='+submitTime+'&url='+document.location.href;

	// Add younicorns if requested.
	if(options && (options.y || options.younicorns)) {
		url += '&y='+(options.y ? options.y : options.younicorns);

        if(Math.random() > 0.5) {
            // Flip horizontally at random.
            div.style.transform = 'scaleX(-1)';
        };
	}

	img.setAttribute('src', url);
	
	// Add a nice hover transition.
	var ease = "all .1s linear";
	div.style.WebkitTransition = ease;
	div.style.WebkitTransform = "rotate(1deg) scale(1.01,1.01)";
	div.style.transition = "all .1s linear";

	div.onmouseover = function() {
		var size = 1 + Math.round(Math.random()*10)/100;
		var angle = Math.round(Math.random()*20-10);
		var result = "rotate("+angle+"deg) scale("+size+","+size+")";
		this.style.transform = result;
		this.style.WebkitTransform = result;
	};

	div.onmouseout = function() {
		var size = .9+Math.round(Math.random()*10)/100;
		var angle = Math.round(Math.random()*6-3);
		var result = "rotate("+angle+"deg) scale("+size+","+size+")";
		this.style.transform = result;	
		this.style.WebkitTransform = result;
	};

	// Append our container DIV to the page.
	var body = document.getElementsByTagName('body')[0];
	body.appendChild(div);
	div.appendChild(img);	

	// Hooray - now we have a sparkly unicorn (or rainbow) on the page. Another cornification well done. Congrats!
	
	// When clicking 5 times, add a custom stylesheet to make the page look awesome.
	if(cornify_count == 5) {
		var cssExisting = document.getElementById('__cornify_css');

		if(!cssExisting) {
			var head = document.getElementsByTagName("head")[0];
			var css = document.createElement('link');
			css.id = '__cornify_css';
			css.type = 'text/css';
			css.rel = 'stylesheet';
			css.href = 'https://www.cornify.com/css/cornify.css';
			css.media = 'screen';
			head.appendChild(css);
		}
		cornify_replace();
	}	
	
	cornify_updatecount();
};

// Tracks how often we cornified.
var cornify_updatecount = function() {
	var p = document.getElementById('cornifycount');
	if(p == null) {
		var p = document.createElement('p');
		p.id = 'cornifycount';
		p.style.position = 'fixed';
		p.style.bottom = '5px';
		p.style.left = '0px';
		p.style.right = '0px';
		p.style.zIndex = '1000000000';
		p.style.color = '#ff00ff';
		p.style.textAlign = 'center';
		p.style.fontSize = '24px';
		p.style.fontFamily = "'Comic Sans MS', 'Comic Sans', 'Marker Felt', serif"; // Only the best!
		var body = document.getElementsByTagName('body')[0];
		body.appendChild(p);
	}

	if(cornify_count == 1) {
		p.innerHTML = cornify_count+' UNICORN OR RAINBOW CREATED';
	} else {
		p.innerHTML = cornify_count+' UNICORNS &AMP; RAINBOWS CREATED';
	}

	// Stores our count in a cookie for our next session.
	cornify_setcookie('cornify', cornify_count+'', 1000);
};

var cornify_setcookie = function(name, value, days) {
	var d = new Date();
	d.setTime(d.getTime()+(days*24*60*60*1000));
	var expires = "expires="+d.toGMTString();
	document.cookie = name + "=" + value + "; " + expires;
};

var cornify_getcookie = function(cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i].trim();
		if(c.indexOf(name)==0) {
			return c.substring(name.length,c.length);
		}
	}
	return "";
};

// Retrieve our click count from the cookie when we start up.
cornify_count = parseInt(cornify_getcookie('cornify'));
if(isNaN(cornify_count)) {
	cornify_count = 0;
}

// Adds happy words at the beginning of all headers on the page.
var cornify_replace = function() {
	// Replace text.
	var hc = 6;
	var hs;
	var h;
	var k;
	var words = ['Happy','Sparkly','Glittery','Fun','Magical','Lovely','Cute','Charming','Amazing','Wonderful'];
	while(hc >= 1) {
		hs = document.getElementsByTagName('h' + hc);
		for (k = 0; k < hs.length; k++) {
			h = hs[k];
			h.innerHTML = words[Math.floor(Math.random()*words.length)] + ' ' + h.innerHTML;
		}
		hc-=1;
	}
};

/*
 * Adapted from http://www.snaptortoise.com/konami-js/
 */
var cornami = {
	input:"",
	pattern:"38384040373937396665",
	clear:setTimeout('cornami.clear_input()', 5000),
	load: function() {
		window.document.onkeydown = function(e) {
			if (cornami.input == cornami.pattern) {
				cornify_add();
				clearTimeout(cornami.clear);
				return;
			}
			else {
				cornami.input += e ? e.keyCode : event.keyCode;
				if (cornami.input == cornami.pattern) cornify_add();
				clearTimeout(cornami.clear);
				cornami.clear = setTimeout("cornami.clear_input()", 5000);
			}
		};
	},
	clear_input: function() {
		cornami.input="";
		clearTimeout(cornami.clear);
	}
};

cornami.load();
