'use strict';

//Constants
var UPDATE_DELAY = 25000;       //millisecond delay between data udpates
var FRAMERATE_DELAY = 50;       //millisecond delay between frames
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


/**
 * Display class - for managing panels. This doesn't make decisions about what modules show in which panel... just the abstraction layer for managing panels. 
 * It,
 * 1) Generates/Stores the panels
 * 2) Handles resizing
 * 3) Handles layout
 * 
 * Each panel will be externally assigned to a module. The hierarchy is:
 *   Display -> Slot -> Module -> Panel
 */
 function Display() {
    //Constants
    this.const = {
        SCREEN_LARGE: 1,
        SCREEN_MEDIUM: 2,
        SCREEN_SMALL: 3
    };

    //Config
    this.config = {
        layout: "default",    //Panel layout
        slots: 4
    };

    //DOM/Abstract objects
    this.obj = {
        MAIN: $("#main"),       //Main container
        PANELS: [],             //Panel objects
        SLOTS: []               //Available visual slots (can change over time)
    };

    this.tmp = {
        resizeTID: false,
        numActive: 0,           //Number of active panels
        numSlots: 0,             //Currently now many slots are visible on screen (most to least priority) - assuming this will always be an even number
        isLandscape: true,      //Is screen landscape orientation?
        screenSize: false       //Screen size
    };

    this.isReady = false;
}

/**
 * Initialize Display
 */
Display.prototype.Init = function() {   
    //Initialize slots
    for(var i = 0; i < this.config.slots; i++) {
        var slot = new Slot(i);
        this.obj.SLOTS.push(slot);        
    }
    this.tmp.numSlots = this.config.slots;   
}

/**
 * Requests a new panel
 */
Display.prototype.RequestNewPanel = function() {
    var index = this.obj.PANELS.length;
    var panel = new Panel();
    panel.Init(this,index);        
    this.obj.PANELS.push(panel);
    return panel;
}

/**
 * Screen resizes
 */
Display.prototype.Resize = function() {

    //Assess the screen 
    var ww = $(window).width();
    var wh = $(window).height();
    this.tmp.isLandscape = ww > wh;
    this.tmp.screenSize = this.const.SCREEN_LARGE;

    if ( ww < 640 ) this.tmp.screenSize = this.const.SCREEN_SMALL;
    else if ( ww < 1024 ) this.tmp.screenSize = this.const.SCREEN_MEDIUM;
    

    //Update CSS context
    $(this.obj.MAIN)
        .removeClass("screensize-" + this.const.SCREEN_LARGE)
        .removeClass("screensize-" + this.const.SCREEN_MEDIUM)
        .removeClass("screensize-" + this.const.SCREEN_SMALL)
        .removeClass("screen-tall")
        .addClass("screensize-" + this.tmp.screenSize);

    if ( !this.tmp.isLandscape )
        $(this.obj.MAIN)
            .addClass("screensize-tall");

    //Determine slot configuration
    this.OrganizeSlots();    

    //See if the moduels have any resizing they want to do
    for(var i in modules) 
        if ( modules[i].Resize != undefined )
            modules[i].Resize(ww);

    this.isReady = true;
 
}


/**
 * Based on the size of the screen, figure out the arrangement of slots on-screen. Can be ran initially, or on resize
 */
Display.prototype.OrganizeSlots = function() {

    var shownSlots = this.config.slots; //Max

    if ( this.tmp.screenSize == this.const.SCREEN_MEDIUM ) shownSlots = 3;
    else if ( this.tmp.screenSize == this.const.SCREEN_SMALL ) shownSlots = 1;
    this.tmp.shownSlots = shownSlots;
    this.tmp.slotSearchIncrement = 0;

    //Re-establish which slotes are available or not based on screen size.
    //Also collect the currently requested panels for re-assignment
    //Both assumed in priority order
    for(var i = 0; i < this.config.slots; i++) {
        if ( i < shownSlots )
            this.obj.SLOTS[i].Show();
        else
            this.obj.SLOTS[i].Hide();

        var panels = this.obj.SLOTS[i].ClearModules();
    }

    //Go through global modules list, from beginning to end, and assign to appropriate slots
    for(var i = 0; i < modules.length; i++) {
        modules[i].core.RequestSlot();           
    }

}

/**
 * Returns the slot index if module is assigned to a slot
 * 
 * @param module The module object
 * 
 * @returns int
 */
Display.prototype.ModuleSlotIndex = function(module) {
    for(var i in this.obj.SLOTS ) {
        if ( this.obj.SLOTS[i].HasModule(module) )
            return i;        
    }
    return -1;
}

/**
 * Panel requests a slot on screen. Returns true if it was successful.
 * 
 * @return bool
 */
Display.prototype.RequestSlot = function(module) {
    var numPerSlot = Math.ceil(modules.length / this.tmp.shownSlots);

    var slotNum = this.ModuleSlotIndex(module); //Where is the module assigned, if anywhere?

    if ( slotNum < 0 ) { //Does the module have a home in a slot? If not,     
        //Assign it
        var slotIndex = this.tmp.slotSearchIncrement % this.tmp.shownSlots;
        this.obj.SLOTS[slotIndex].AssignModule(module);

        this.tmp.slotSearchIncrement++;
        return true;    
    } else { //See if it can take the place of an existing presenting-module
        
        var activeModule = this.obj.SLOTS[slotNum].GetActiveModule();
        if ( activeModule ) {
            //If it's minimum time is up, take over
            var timeActive = (new Date()) - activeModule.core.timeSinceActivated;
            console.log("...",timeActive,activeModule.core.minimumActiveTime);
            if ( timeActive > activeModule.core.minimumActiveTime ) {

                activeModule.core.Disable();
                module.core.Enable();

                return true;

            } else { //Not now
                return false;
            }

        } else {
            // ?
        }

    }

    return false;
}

/**
 * A slot is an avaible space on-screen where a panel can be shown. It does not have a DOM object
 */

 function Slot(i) {
    this.assignedModules = [];   //Which modules are using this slot?
    this.isVisible = true;      //Is this slot currently shown/available on screen?
    this.index = i;
}

/**
 * Hides a slot
 */
Slot.prototype.Hide = function() {
    this.isVisible = false;
}

/**
 * Shows a slot
 */
Slot.prototype.Show = function() {
    this.isVisible = true;
}

/**
 * Adds a panel reference to this slot
 * 
 */
Slot.prototype.AssignModule = function(module) {
    this.assignedModules.push(module);

    //If it's the first one, show it by default
    if ( this.assignedModules.length == 1 )
        module.core.Enable();        
    module.core.panel.ConnectToSlot(this.index);
}

/**
 * Returns the list of assigned panels
 */
Slot.prototype.GetAssignedModules = function() {
    return this.assignedModules;
}

/**
 * Returns true if slot has panel as assigned
 */
Slot.prototype.HasModule = function(module) {
    for(var i in this.assignedModules) {
        if ( this.assignedModules[i].core.id == module.core.id )
            return true;
    }
    return false;
}

/**
 * Returns the current active module
 */
Slot.prototype.GetActiveModule = function() {
    for(var i in this.assignedModules)
        if ( this.assignedModules[i].core.IsActive() )
            return this.assignedModules[i];
    return false;
}

/**
 * Clears panel references, and de-activates the panels
 */
Slot.prototype.ClearModules = function() {

    for(var i in this.assignedModules ) {
        this.assignedModules[i].core.Disable();
        this.assignedModules[i].core.panel.DisconnectFromSlot();
    }
        
    this.assignedModules = [];
}

/**
 * Returns true if this slot has room for a panel of priority level
 * @param priority "size" of module/panel request
 * @param roomPerSlot the calculated max "room" for this slot
 * 
 * @return bool
 */
Slot.prototype.IsAvailable = function(priority,roomPerSlot) {   
    var current = 0;
    for(var i = 0; i < this.assignedModules.length; i++)
        current += this.assignedModules[i].core.GetPriority();
    
    return (roomPerSlot - current) >= priority; 
}



/**
 * Panel class represents a module's canvas to presenting something. Does not necesarily mean it's currently shown, or in any particular slot       
 */
 function Panel() {
    this.htmlObj = false;       //The panel HTML object
    this.active = false;        //Whether this panel is active
    this.displayRef = false;    //Reference back to the display manager class
    this.index = -1;            //What is the index of this panel in the list? (This also doubles as an unique auto-incrementing ID)
}   

/**
 * Initializes and registers a panel with the main container
 */
Panel.prototype.Init = function(displayRef,index) {
    this.displayRef = displayRef;
    this.index = index;
    this.htmlObj = $("<div class='panel'></div>");
    $("#main").append(this.htmlObj);
}

/**
 * Dispable the panel   
 */
Panel.prototype.Disable = function() {
    l("Disabling panel " + this.index);

    this.active = false;
    $(this.htmlObj).hide();
}

/**
 * Enable the panel
 */
Panel.prototype.Enable = function() {
    l("Enabling panel " + this.index);

    this.active = true;
    $(this.htmlObj).show();
}

/**
 * Disassociates/disconnects the panel from a slot. This assumes that no other classes will be applied to the panel.
 */
Panel.prototype.DisconnectFromSlot = function() {
    $(this.htmlObj).attr("class","panel");
}

/**
 * Associates/connects the panel to the slot
 */
Panel.prototype.ConnectToSlot = function(id) {
    $(this.htmlObj).addClass("slot-" + id);
}

/**
 * Places an ID on the panel for uses with CSS, JS, etc
 */
Panel.prototype.SetUniqueId = function(id) {
    $(this.htmlObj).attr("id",id);
}



/**
 * Simple assset loader/preloader
 */

 function Loader(loadingBarSelector) {
    this.loaded = {             //Loaded data, if needed
    };
    this.queue = [];            //Waiting for load
    this.onComplete = false;    //When the queue is done
    this.loadingBarSelector = false;
}

/**
 * Adds a file to the queue
 */
Loader.prototype.Add = function(src) {
    this.queue.push(src);    
}

/**
 * Starts the Load
 */
Loader.prototype.Load = function(callback) {
    if ( this.loadingBarSelector )
        $(loadingBarSelector).show();

    this.onComplete = callback;
    this.LoadNext();
}

/**
 * Requests the process to begin
 */
Loader.prototype.LoadNext = function() {
    var next = this.queue.pop();

    if ( next ) {
        var type = next.match(/\.([a-zA-Z0-9]+)$/);
        if ( type ) {
            var ext = type[1].toLowerCase();
            switch(ext) {
                case 'json':
                    var loader = this;            
                    $.getJSON(next,function(data,success) { loader.OnComplete_Json(this,data,success) });
                break;
                default:
                    console.log("Loader::LoadNext: Type not recognized",ext);
            }
        } else {
            console.log("Loader::LoadNext: No recognized",type);
        }
    } else { //We're done
        if ( this.loadingBarSelector )
            $(loadingBarSelector).hide();

        if ( this.onComplete )
            (this.onComplete)();
    }
}

/**
 * When a JSON file is loaded
 */
Loader.prototype.OnComplete_Json = function(jsonLoader,data,success) {
    this.loaded[jsonLoader.url] = data;
    this.LoadNext();
}

/**
 * Fetches a loaded data objects
 */
Loader.prototype.Get = function(src) {
    if ( this.loaded[src] )
        return this.loaded[src];
    return false;
}






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
        console.log("updateEngine");
        $.getJSON('/report.json',function(data,success) {
            console.log("test", data);
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