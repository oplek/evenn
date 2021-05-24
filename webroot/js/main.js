'use strict';

//Constants
var UPDATE_DELAY = 5000;       //millisecond delay between data udpates
var FRAMERATE_DELAY = 5;       //millisecond delay between frames
var DEBUG = true;   

var LOD_HIGH = 1;
var LOD_MEDIUM = 2;
var LOD_LOW = 3;

var TIME_MINUTE = 60;
var TIME_MINUTE_MS = 60000;

//Persistent data structures
var three;
var display;
var modules;

var currentLOD = LOD_HIGH;  //What is our current level of detail?

//Initialization and loading
$(document).ready(function() {
    l("Loading");

    var module_BattleReport = new ModuleBattleReport();

    modules = [
        module_BattleReport
    ];    

    //Set up display
    // = new ThreeHelper("#main");   //3D rendering abstraction
    display = new Display();            //The conceptual organization of modules/displays
    display.Init();    
    $(window).bind("resize",function() {
        display.Resize();
    });  

    //...Run module initialization
    var numComplete = 0;
    for(var i in modules) {
        var panel = display.RequestNewPanel(); //Register new panel to give to module

        modules[i].Init(panel, i, function() { 
            l("Module " + i + " loaded");
            modules[i].core.SetReady(true);

            //Asyncronously allow the modules to load their own things, if any
            numComplete++;
            if ( numComplete >= modules.length ) {
                display.Resize(); //Once more for the road
                main();
            } else {
                //TODO: Load bar
            }            
        });
    }
});

//Post-load main functionality
function main() {
    l("Initailizing main engines");

    //Main data update cycle
    //========================================
    var roundRobinIndex = 0;
    var firstRun = true;
    var lastPlayerCount = 0;
    var lastUpdated = now();
    function updateEngine() {
        $.getJSON('/report.json',function(data,success) {
            if (success) {                
                
                for(var i in modules) {                                          
                    if ( i == roundRobinIndex || modules[i].priority == 1 || firstRun ) { //Priority-1 panels will use discretion as to whether to update
                        modules[i].UpdateData(data);
                        modules[i].UpdatePanel(currentLOD); //The panel will negotiate with the Display manager where/how it'll show
                        //Modules decide when/how they request an active slot
                    }
                }
                roundRobinIndex++;
                roundRobinIndex = roundRobinIndex % modules.length;

                $("#uptime_counter").html(Math.floor(data.ut/60) + (data.wu ? "min [<span class='pulse'>warming up</span>]" : "min"));
                $("#online_counter").text(data.pc);

                //------------------------------
                lastUpdated = now();
                setTimeout(updateEngine,UPDATE_DELAY);

                //Wait until we have our first data load before starting render
                if ( firstRun ) {
                    runTID = setTimeout(runEngine,FRAMERATE_DELAY);
                    firstRun = false;
                }                

            } else {
               //Do something
            }

        });  
    }
    updateEngine();

    //Main framerate cycle
    //========================================
    var runTID = false;     
    var lastTS = now();
    function runEngine() {  
        //The module will keep animating away until the Display manager revokes its slot
        for(var i in modules)
            modules[i].Run(lastTS);  //Pass last-timestamp instead of delta, as delta may change as we go through the modules


        TWEEN.update();
        runTID = setTimeout(runEngine,FRAMERATE_DELAY);
        lastTS = now();   

        if ( lastTS - lastUpdated > 600 ) //Something went wrong - reload
            window.location.reload();     
    }
    

}


/**
 * Returns now in seconds
 */
function now() {
    return (new Date()).getTime() / 1000;
}

/**
 * Debug log wrapper
 * @param {string} str 
 */
function l(str) { if ( DEBUG ) console.log('DEBUG',str); }

/**
 * Generates image URL
 * 
 * @param id 
 *   ID of item.
 * @param size 
 *   Resolution of item (32, 64, 128, etc).
 * 
 * @return string
 */
function itemImage(id,size) {
    return "https://imageserver.eveonline.com/Render/"+id+"_"+size+".png";
}

/**
 * Conerts THREE.Vector3 to simple object
 */
function toSV(v) {
    return {x:v.x,y:v.y,z:v.z};
}