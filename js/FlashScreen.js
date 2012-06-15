
/*
    Document   : tables
    Created on : Jun 13, 2012
    Author     : Daniel Billings
    Description:
        Fullscreenizes a flash element

WARNING: This JS file is designed to work with the mochi arcade auto post plugin,
and makes several assumptions, as such it is not portable, and won't work with
just ANY flash element.  It will be updated at some point in the future.
This also adds a member to the setInterval function as a reference to itself.
*/

//just trying to give my variables a unique namespace here
function mochiFelps()
{
	var done = false;
	var working = false;
	var original = Array({x:0,y:0,height:0,width:0},{x:0,y:0,height:0,width:0});
	var divToMove = null;
	var otherDiv = null;
	var theInterval = null;
	var currentWinSize = null;
	var selfRef = this; //unexpected results when an outside method attempts to call a function passed to it as a parameter without this.

	selfRef.toggleFull = function()
	{
		if(working)
		{
			var obj = document.getElementById(divToMove);
			var test = obj;
			done = true;
			window.clearInterval(theInterval);
			selfRef.setDiv();
			if(test.cancelFullScreen)
			test.cancelFullScreen();
			else
			if(test.mozCancelFullScreen)
				test.mozCancelFullScreen();
			else
			if(test.webkitCancelFullScreen)
				test.webkitCancelFullScreen();
		}
		else
		{
			divToMove = 'mochi_game';
			otherDiv = 'fullscreen_link';
			//hack hack hackity hack hack hack
			//adding a new member to setInterval called selfRef, it is a reference to this
			//but not the this that is setInterval, the this that is this
			//I should note that this, among other factors will prevent this from working when multiple games are on one page.
			window.setInterval.selfRef = selfRef;
			theInterval = window.setInterval(selfRef.checkSize, 500);
			selfRef.setDiv();
		}
	};
	selfRef.checkSize = function()
	{
		var win = selfRef.getWindowSize();
		if(win.width != currentWinSize.width || win.height != currentWinSize.height)
		{
			selfRef.setDiv();
			
		}
		var theInputChecker = document;
		theInputChecker.selfRef = selfRef;
		if(theInputChecker.attachEvent)
			theInputChecker.attachEvent("onkeyup", selfRef.checkEsc, false);
		else
			theInputChecker.addEventListener("keyup", selfRef.checkEsc);
	};
	selfRef.setDiv = function()
	{
		var obj = document.getElementById(divToMove);
		var test = obj;
		var win = selfRef.getWindowSize();
		var obj2 = document.getElementById(otherDiv);
		var adminBar = document.getElementById('wpadminbar'); //the wordpress admin bar
		var theBody = document.getElementsByTagName("body")[0];
		currentWinSize = win;
			if(test.mozRequestFullScreen)
				test.mozRequestFullScreen();
			else
			if(!done)
			{
				obj.style.position = "fixed";
				obj2.style.position = "fixed";
				if(working == false)
				{
					original[0].width = obj.style.width;
					original[0].height = obj.style.height;
					original[1].x = obj2.style.left;
					original[1].y = obj2.style.top;
					working = true;
					
				}
				if(test.requestFullScreen)
						test.requestFullScreen();
					else
					if(test.webkitRequestFullScreen)
						test.webkitRequestFullScreen();
				test.selfRef = selfRef;
				test.addEventListener("mozfullscreenchange", function () {
				selfRef.toggleFull();
				}, false);
				obj.style.top = '0px';
				obj.style.left = '0px';
				obj2.style.top = '0px';
				obj2.style.left = win.width/2 + 'px';
				obj.style.width = win.width + 'px';
				var theHeight = win.height;// - obj2.offsetHeight;
				if(adminBar !== null) //If the admin bar is up
				{
					obj.style.top = '28px'; //Set game window's top to the height of the wordpress admin bar
					theHeight = theHeight - 28; //subtract the height of the admin bar
					obj2.style.top = '28px';
				}
				obj.style.height = theHeight + 'px';

			}
			else
			{
				//set div back to original position, and reset state
				obj.style.top = "0px";
				obj.style.left = "0px";
				obj.style.width = original[0].width;
				obj.style.height = original[0].height;
				obj.style.position = "static";
				obj2.style.position = "relative";
				obj2.style.top = original[1].y;
				obj2.style.left = original[1].x;
				working = false;
				//mochiFelpsVars.theInterval = window.clearInterval(mochiFelpsVars.theInterval);
				done = false;
				theBody.onkeyup = null;
			}
			//test = obj;


	};
	selfRef.getWindowSize = function()
	{
		//also not written by Felps, I believe it was minimified... modified it to be a function that returns x and y as width and height
		var w=window,d=document,e=d.documentElement,g=d.getElementsByTagName('body')[0],x=w.innerWidth||e.clientWidth||g.clientWidth,y=w.innerHeight||e.clientHeight||g.clientHeight;

		return {width: x,height: y};
	};
	selfRef.checkEsc = function(e)
	{
		var theKey = e.keyCode || e.which;
		if(theKey == 27)
			selfRef.toggleFull();
	}
}
var mochiArcadeAutoPostJS = new mochiFelps();