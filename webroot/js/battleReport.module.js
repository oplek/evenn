

/**
 * Battle report module
 */
 function ModuleBattleReport() {
    this.data = {                   //Internal data processing/storage
        
        mapIsLoaded:false,          //Busy playing out a battle report?
        dataIsLoaded:false,
        mapMode: false,             //What is the map display currently doing?
        
        br_index: 0,                //Which one are we looking at?
        highlightTimeout: now(),    //When will the current highlight expire?
        highlightCooldown: now(),   //When will the next highlight be allowed?

        //Three object storage
        three:{
            materials: {}           //Keep track of materials
        },
        pos_camLookAt: {x:0,y:0,z:0}  //Current look-at coordinates
    };
    this.const = {
        rot_mapOverhead: [0,Math.PI/2,0],
        rot_mapSpin: [0,0.3,0],
        pos_camOrig: {x:0,y:-500,z:5500},
        pos_camOrigLookAt: {x:0,y:-500,z:0},
        highlightOffset: 100.5,

        MAPMODE_DEFAULT: 1,
        MAPMODE_HIGHLIGHT: 2,
        MAPMODE_HIGHLIGHT_SYSTEM: 3,
        MAPMODE_HIGHLIGHT_LOADING: 4,
        MAPMODE_HIGHLIGHT_LOCATION: 5
    };
    this.core = new CoreModule(this);    
}


/**
 * Initialize
 */
ModuleBattleReport.prototype.Init = function(panel,id,onComplete) {
    this.core.Init(panel,id);
    //--------------------------------------------------------------------

    this.data.mapMode = this.const.MAPMODE_DEFAULT;

    $(this.core.PanelHtml())
        .append("<div id='br_container_starmap'></div>")
        .append("<div id='br_container_system'></div>")
        .append("<div id='br_container_br'></div>")
        .append("<div class='loading' id='br_loading'>Loading...</div>");
    this.core.panel.SetUniqueId('br');

    //Set up renderers
    this.threeStarmap = new ThreeHelper('#br_container_starmap');    
    this.threeSystem = new ThreeHelper('#br_container_system');    
    this.threeLocation = new ThreeHelper('#br_container_br');   
    
    //Extra HTML/hud layers
    $("#br_container_br").append("<div class='hud bottom'></div>");
    $("#br_container_br").append("<div class='hud top'></div>");
    
    
    this.loader = new Loader('#br_loading');

    var self = this;    
    this.loader.Add('webfiles/starmap.json');
    this.loader.Load(function() {
        self.data.mapIsLoaded = true;
        self.InitStarmap();
        onComplete(); //Done loading - make sure this part is loaded before anything else loads
    });  
     
}

/**
 * Provide data to update the module (process the data)
 */
ModuleBattleReport.prototype.UpdateData = function(newdata) {
    //--------------------------------------------------------------------
    this.data.brref = newdata.brref;
    this.data.sysref = newdata.sysref; 
    this.data.hm = newdata.hm;
}

/**
 * (re)Establish the panel presentation, if needed
 * @param obj panel The panel object
 * @param int lod A level-of-detail level indicator (1 lowest, 3 highest) 
 */
ModuleBattleReport.prototype.UpdatePanel = function(lod) {
    this.core.RequestSlot(); //Panel will negotiate with Display class
    //--------------------------------------------------------------------

    /*
    var html = "";
    for(var i in this.data.brref ) {
        var br = this.data.brref[i];
        var sysref = this.data.sysref[br.sid];
        var locref = sysref[4][br.locid];
        
        //--
        html += "<div class='br clear'>";
        html += "<div class='name'>" + sysref[1] + "</div>";
        html += "<div class='region'>" + sysref[2] + "</div>";
        html += "<div class='loc'>" + locref[1] + "</div>";
        html += "</div>";

    }
    $("#br_container").html(html);
    */    

    this.isReady = true;
}


/**
 * Handles presentation updates and mode switches
 */
ModuleBattleReport.prototype.RunPresentation = function(lastTs) {
    var ts = now();
    var delta = now - lastTs;

    //Mode-switching events
    //=======================================


    //Show a battle report
    if ( this.data.mapMode == this.const.MAPMODE_DEFAULT && ts > this.data.highlightCooldown ) { //Let's highlight one of them
        var br = this.data.brref[this.data.br_index];
        if ( br ) {
        
            var self = this;
            self.data.mapMode = self.const.MAPMODE_HIGHLIGHT_LOADING;
            $.getJSON("/report.json",{id:br.ts},function(data,success) {
                //self.data.highlightTimeout = ts + TIME_MINUTE * 0.10;
                self.data.mapMode = self.const.MAPMODE_HIGHLIGHT_SYSTEM;
                self.Camera_FocusOnStar(br.sid,function() {            
                    self.data.three.systemBR.Init(self.data.sysref[br.sid],data);
                    self.data.three.locationBR.Init(self.data.sysref[br.sid],data,function() {
                        //Battle complete

                        self.Camera_Reset();
                        self.data.three.systemBR.ResetScene();
                        self.data.three.locationBR.ResetScene();

                        self.data.highlightCooldown = now() + TIME_MINUTE * 0.16;
                        self.data.br_index++;
                        self.data.br_index = self.data.br_index % self.data.brref.length;
                        self.data.mapMode = self.const.MAPMODE_DEFAULT;

                    });
                });
            });
        }
    }

}


/**
 * Attempt to do a frame update
 */
ModuleBattleReport.prototype.Run = function(lastTS) {
    if ( !this.core.IsReadyAndActive() || !this.data.initialized ) return;
    var delta = now() - lastTS;
    //--------------------------------------------------------------------
    
    //this.data.three.stars.rotation.y += delta * 1.5;
    //this.data.three.star_uniforms.time.value += delta/10;
    //this.three.renderer.autoClear = false;

    //this.threeStarmap.Clear(); 
    this.threeStarmap.Render(this.data.three.scene_starmap);   
    
    //Do system scene, if we're doing it
    //if (this.data.mapMode == this.const.MAPMODE_HIGHLIGHT_SYSTEM && this.data.three.systemBR.isActive ) {
        this.data.three.systemBR.Run(lastTS);
        this.threeSystem.Render(this.data.three.systemBR.scene);
    //}

    //Do the location scene
    //if ( this.data.mapMode == this.const.MAPMODE_HIGHLIGHT_SYSTEM && this.data.three.locationBR.isActive ) {
        this.data.three.locationBR.Run(lastTS);
        this.threeLocation.Render(this.data.three.locationBR.scene);
    //}

    //Run this after the three.Render to ensure matrices are updated
    this.RunPresentation(lastTS);
}



/**
 * 
 */
ModuleBattleReport.prototype.Resize = function() {
    var pw = this.core.PanelWidthInner();
    var ph = this.core.PanelHeightInner();

    //if ( this.threeStarmap )
        this.threeStarmap.Resize(pw,ph);
    //if ( this.threeSystem )
        this.threeSystem.Resize(pw,ph);
    //if ( this.threeBR )
        this.threeLocation.Resize(pw,ph);
    
    if ( this.data.three.systemBR ) {
        this.data.three.systemBR.Resize();
    }
    if ( this.data.three.locationBR ) {
        this.data.three.locationBR.Resize();
    }
}




/**
 * Do the rest of the complex setup - starmap
 */
ModuleBattleReport.prototype.InitStarmap = function() {
    this.data.three.scene_starmap = this.threeSystem.NewScene();

    //Establish the stars
    var geometry = new THREE.BufferGeometry();
    this.data.starmap = this.loader.Get("webfiles/starmap.json");
    this.data.numPoints = this.data.starmap.numStars;

    //Gate data
    //-----------------------
    var positions = new Float32Array( this.data.numPoints * 3 );
    var colors = new Float32Array( this.data.numPoints * 3 );
    var sizes = new Float32Array( this.data.numPoints );

    var k = 0;
    for(var i in this.data.starmap.stars ) {
        var star = this.data.starmap.stars[i];

        positions[ 3 * k ] = star[0];
        positions[ 3 * k + 1 ] = star[1];
        positions[ 3 * k + 2 ] = star[2];
       
        colors[ 3 * k ] = 1;//0.5*star[3] + 0.55;
        colors[ 3 * k + 1 ] = 1;//0.5*star[3] + 0.55;
        colors[ 3 * k + 2 ] = 1;//0.5*star[3] + 0.55;

        sizes[k] = 0.15;

        k++;
    }

    geometry.addAttribute( 'position', new THREE.BufferAttribute( positions, 3 ) );
    geometry.addAttribute( 'color', new THREE.BufferAttribute( colors, 3 ) );
    geometry.addAttribute( 'size', new THREE.BufferAttribute( sizes, 1 ) );
    geometry.computeBoundingBox();

    //var textureLoader = new THREE.TextureLoader();
	//var sprite1 = textureLoader.load( 'webfiles/images/sprite_heatmap.png' );
    var material = new THREE.PointsMaterial( { 
        size: 0.025, 
        vertexColors: THREE.VertexColors,
        //map: sprite1, 
        blending: THREE.AdditiveBlending, 
        depthTest: true, 
        transparent: true
    } );
    this.data.three.materials.starmap_stars = material;

    this.data.pointcloud = new THREE.Points( geometry, material );

    //Gate data
    //-----------------------
    var linePoints = [];
    var s1,s2,g;
    for(var i in this.data.starmap.gates ) {
        g = this.data.starmap.gates[i];
        s1 = this.data.starmap.stars[g[0]];
        s2 = this.data.starmap.stars[g[1]];

        linePoints.push(s1[0],s1[1],s1[2],s2[0],s2[1],s2[2]);
    }
    var lineMaterial = new THREE.LineBasicMaterial({
        color: 0x0000ff,
        opacity: 0.5,
        transparent: true,
        depthTest: true
    });
    this.data.three.materials.starmap_gates = lineMaterial;
    var lineGeometry = new THREE.BufferGeometry();   
    lineGeometry.addAttribute( 'position', new THREE.Float32BufferAttribute( linePoints, 3 ) );
    this.data.gateLines = new THREE.LineSegments( lineGeometry, lineMaterial );

    //Focus spot (for focusing)
    this.data.three.focusSpot = new THREE.Object3D();   
    

    //Create group
    this.data.three.stars = new THREE.Object3D();
    this.data.three.stars.add(this.data.pointcloud);
    this.data.three.stars.add(this.data.gateLines);
    this.data.three.stars.add(this.data.three.focusSpot);

    this.data.three.scene_starmap.add(this.data.three.stars);
    this.data.three.stars.rotation.x = -Math.PI/2;
    this.data.three.stars.scale.set(100,100,100);

    //Re-usable star
    /*this.data.three.star_uniforms = {
        time: { value: 1.0 }
    };
    var starMat = new THREE.ShaderMaterial( {
        uniforms: this.data.three.star_uniforms,
        vertexShader: document.getElementById( 'br_vert_star' ).textContent,
        fragmentShader: document.getElementById( 'br_frag_star' ).textContent
    } );

    this.data.three.starObj = new THREE.Mesh( new THREE.SphereBufferGeometry( 1, 20, 10 ), starMat );
    this.data.three.scene_starmap.add(this.data.three.starObj);*/

    //var light = new THREE.DirectionalLight( 0xffffff, 1 );
    //light.position.set( 1, 1, 1 ).normalize();
    //this.three.scene.add( light );


    this.threeStarmap.MoveCamera(this.const.pos_camOrig,this.const.pos_camOrigLookAt);
    this.Resize();

    //Pre-initialize the system-scene
    this.data.three.systemBR = new ModuleBattleReport_SubmoduleSystem(this);
    this.data.three.systemBR.scene = this.threeSystem.NewScene();

    //Pre-initialize the location-scene
    this.data.three.locationBR = new ModuleBattleReport_SubmoduleLocation(this);
    this.data.three.locationBR.scene = this.threeSystem.NewScene();
   
    //Init complete
    //---------------------------------------
    this.data.initialized = true;
    this.core.SetReady(true);
}


/**
 * Fetches a star's global position
 */
ModuleBattleReport.prototype.GetStarLoc = function(id) {
    var star = this.data.starmap.stars[id];
    if ( star ) {       
        this.data.three.focusSpot.position.set(star[0],star[1],star[2]);
        var a = new THREE.Vector3();
        this.data.three.focusSpot.getWorldPosition(a);       
        return a;
    }
    return false;
}

/* Focuses the map on a star */
ModuleBattleReport.prototype.Camera_FocusOnStar = function(id,callback) {
    var gPos = this.GetStarLoc(id);
    if ( gPos ) {  

        var self = this;
      
        var delay = 2000;
        var cOffset = {x:gPos.x,y:gPos.y,z:gPos.z + this.const.highlightOffset};

        //Move cammera
        var camPos = toSV(self.threeStarmap.camera.position);
        var tweenPos = new TWEEN.Tween(camPos)
            .to(cOffset,delay)
            .easing(TWEEN.Easing.Quadratic.InOut)
            .onUpdate(function(){
                self.threeStarmap.camera.position.x = camPos.x;
                self.threeStarmap.camera.position.y = camPos.y;
                self.threeStarmap.camera.position.z = camPos.z;
            })
            .start();  

        //Focus camera
        var laPos = toSV(self.const.pos_camOrigLookAt);
        var tweenLA = new TWEEN.Tween(laPos)
            .to(gPos,delay)
            .easing(TWEEN.Easing.Quadratic.InOut)
            .onUpdate(function(){
                self.threeStarmap.camera.lookAt(new THREE.Vector3(laPos.x,laPos.y,laPos.z));
                self.data.pos_camLookAt = laPos;
            })
            .start();

        //Fade out starmap
        var prop = {o:1};
        var tweenOpacity = new TWEEN.Tween(prop)
            .to({o:0},delay*0.1)
            .delay(delay*0.9)
            .onUpdate(function() {                
                self.data.three.materials.starmap_stars.opacity = prop.o + 0.25;
                self.data.three.materials.starmap_gates.opacity = prop.o * 0.25 + 0.25;
            })
            .onComplete(function() {
                //self.data.three.stars.visible = false;
            })
            .start();

        //If there's something to do after transition
        if ( callback )
            setTimeout(callback,delay+10);
    }
}

/* Focuses the map on a star */
ModuleBattleReport.prototype.Camera_Reset = function() {

    var delay = 750;
    var self = this;      

    //Position
    var camPos = toSV(self.threeStarmap.camera.position);
    var tweenPos = new TWEEN.Tween(camPos)
        .to(self.const.pos_camOrig,delay)
        .easing(TWEEN.Easing.Quadratic.InOut)
        .onUpdate(function(){
            self.threeStarmap.camera.position.x = camPos.x;
            self.threeStarmap.camera.position.y = camPos.y;
            self.threeStarmap.camera.position.z = camPos.z;
        })
        .start();

    //Look at
    var laPos = self.data.pos_camLookAt;
    var tweenLA = new TWEEN.Tween(laPos)
        .to(self.const.pos_camOrigLookAt,delay)
        .easing(TWEEN.Easing.Quadratic.InOut)
        .onUpdate(function(){
            self.threeStarmap.camera.lookAt(new THREE.Vector3(laPos.x,laPos.y,laPos.z));
            self.data.pos_camLookAt = laPos;  
        })
        .start();

    //Fade in starmap
    var prop = {o:0};
    var tweenOpacity = new TWEEN.Tween(prop)
        .to({o:1},delay*0.1)
        .onUpdate(function() {
            self.data.three.materials.starmap_stars.opacity = prop.o + 0.25;
            self.data.three.materials.starmap_gates.opacity = prop.o*0.25 + 0.25;
        })        
        .onStart(function() {
            //self.data.three.stars.visible = true;              
        })
        .start();
        
}


































/**
 * ======================================================================
 */






/* Sub-module for handling system/ships */
function ModuleBattleReport_SubmoduleSystem(parent) {
    this.isActive = false;
    this.scene = false;
    this.parent = parent;
    this.data = {
        three: {
            mats: {},
            objs: {
                spheres: [],
                rings: []
            },
            uniforms: {
                time: { value: 1.0 }
            },
            scaleFactor: 1
        }
    };  
    this.const = {
        SYSTEM_WIDTH: 30
    }  

    //
    
}

/**
 * 
 */
ModuleBattleReport_SubmoduleSystem.prototype.Run = function(lastTS) {
    if ( !this.isActive ) return;
    //

}

/**
 * 
 */
ModuleBattleReport_SubmoduleSystem.prototype.Resize = function() {
    if ( !this.isActive ) return;
    //

}

/**
 * Initialize with system data and battle report
 */
ModuleBattleReport_SubmoduleSystem.prototype.Init = function(sysref,br) {
    var self = this;

    //First-run initializations
    if ( !this.data.isInitialized )
       this.InitOnce(sysref,br);    

    this.data.sysref = sysref;
    this.data.br = br;
    console.log("ModuleBattleReport_SubmoduleSystem",sysref,br);

    //Set target to location
    var loc = false;
    try {
        loc = sysref.obj[br.locid];
    } catch(e) {}
    if ( loc != undefined && loc != false ) {
        this.data.three.target.position.set(-loc[2].x,0,loc[2].z); //Translate into presentation coordinates

        //Blink/highlight
        var targetAnimate = {t:0};
        var targetConst = Math.PI * 2 * 10; 
        this.data.three.target.visible = true;
        new TWEEN.Tween(targetAnimate)
            .to({t:1},1000)
            .easing(TWEEN.Easing.Linear.None)
            .onUpdate(function(){
                self.data.three.mats.targetMat.opacity = Math.cos(targetAnimate.t*targetConst) > 0 ? 1 : 0;
            })
            .start();
        
        //Move system model
        var systemAnimate = {rx:-Math.PI/2,py:0};
        new TWEEN.Tween(systemAnimate)
            .to({rx:-Math.PI/2-1.5,py:-400},1000)
            .delay(750)
            .easing(TWEEN.Easing.Quadratic.InOut)
            .onUpdate(function() {
                self.data.three.system.rotation.x = systemAnimate.rx;
                self.data.three.system.position.y = systemAnimate.py;
            })

            .start();

        //Focus on location    

    } else {
        this.data.three.target.position.set(-1000,-1000,-1000); //Hide it
    }

    //Determine max radius (in au)
    var maxRadiusSqr = 0.1;
    for(var i in sysref.obj) {
        var oRef = sysref.obj[i];
        var distSqr = oRef[2].x * oRef[2].x + oRef[2].z * oRef[2].z;
        if ( distSqr > maxRadiusSqr ) maxRadiusSqr = distSqr;
    }
    var radius = Math.sqrt(maxRadiusSqr);
    this.data.three.scaleFactor = this.const.SYSTEM_WIDTH / radius; 

    //Add star/planets
    obj = this.GetSphere(this.data.three.mats.starMat);    
    obj.name = "sun";
    obj.scale.set(0.25,0.25,0.25);


    for(var i in sysref.obj) {
        var oRef = sysref.obj[i];
        if ( oRef[0] == "p" ) {
            //Planet
            obj = this.GetSphere(this.data.three.mats.planetMat);
            obj.name = "planet " + i;
            obj.scale.set(0.25,0.25,0.25);
            obj.position.set( //Translate into presentation coordinates
                oRef[2].x * -this.data.three.scaleFactor, 
                0,//oRef[2].y * -this.data.three.scaleFactor,               
                oRef[2].z * this.data.three.scaleFactor
            );    
            
            //Orbit
            var radius = Math.sqrt(obj.position.x*obj.position.x + obj.position.z*obj.position.z);
            obj = this.GetRing(this.data.three.mats.orbitMat);
            obj.name = "orbit " + i;
            obj.scale.set(radius,radius,radius);
        }
    }

}

/**
 * One-time init
 */
ModuleBattleReport_SubmoduleSystem.prototype.InitOnce = function(sysref,br) {
     //Set up system object handler
     this.data.three.system = new THREE.Object3D();
     var s = this.const.SYSTEM_WIDTH;
     this.data.three.system.scale.set(s,s,s);
     

     //Set up common materials
     this.data.three.mats.starMat = new THREE.ShaderMaterial( {
         uniforms: this.data.three.uniforms,
         vertexShader: document.getElementById( 'br_vert_star' ).textContent,
         fragmentShader: document.getElementById( 'br_frag_star' ).textContent
     } );
     this.data.three.mats.planetMat = new THREE.ShaderMaterial( {
         uniforms: this.data.three.uniforms,
         vertexShader: document.getElementById( 'br_vert_planet' ).textContent,
         fragmentShader: document.getElementById( 'br_frag_planet' ).textContent
     } );
     this.data.three.mats.orbitMat = new THREE.LineBasicMaterial({
         color: 0xdddddd,
         opacity: 0.25,
         transparent: true,
         depthTest: true
     });

     //Establish target
     var targetMap = new THREE.TextureLoader().load("webfiles/images/sprite_target.png");
     this.data.three.mats.targetMat = new THREE.SpriteMaterial({ 
         map: targetMap, 
         color: 0xffffff,
         sizeAttenuation: false 
     });
     this.data.three.target = new THREE.Sprite(this.data.three.mats.targetMat);  
     this.data.three.target.name = "target";
     var ts = 0.05/s;
     this.data.three.target.scale.set(ts,ts,ts);     
     this.data.three.system.add(this.data.three.target);

     this.data.three.system.rotation.x = -Math.PI/2;
     this.scene.add(this.data.three.system);
     this.data.isInitialized = true;
     this.isActive = true;
}

/**
 * Fetches an idle Sphere, or spawns a new one
 */
ModuleBattleReport_SubmoduleSystem.prototype.GetSphere = function(material) {
    //Look for idle
    var o;
    for(var i in this.data.three.objs.spheres ) {
        o = this.data.three.objs.spheres[i];
        if ( o.isIdle ) {
            o.isIdle = false;
            o.visible = true;
            o.material = material;
            return o;
        }
    }

    o = new THREE.Mesh( new THREE.SphereBufferGeometry( 1, 20, 10 ), material );
    o.isIdle = false;

    this.data.three.objs.spheres.push(o);
    this.data.three.system.add(o);

    return o;
}

/**
 * Fetches an idle Ring, or spawns a new one
 */
ModuleBattleReport_SubmoduleSystem.prototype.GetRing = function(material) {
    //Look for idle
    var o;
    for(var i in this.data.three.objs.rings ) {
        o = this.data.three.objs.rings[i];
        if ( o.isIdle ) {
            o.isIdle = false;
            o.visible = true;
            o.material = material;
            return o;
        }
    }

    var linePoints = [];
    var lx=1,lz=0; //Last points
    var segments = 64;
    for(var i = 0; i <= segments; i++) {
        var r = (i/segments) * (Math.PI*2);
        var x = Math.cos(r);
        var z = Math.sin(r);
        linePoints.push(lx,0,lz,x,0,z);
        lx = x; lz = z;
    }
    
    var lineGeometry = new THREE.BufferGeometry();   
    lineGeometry.addAttribute( 'position', new THREE.Float32BufferAttribute( linePoints, 3 ) );
    o = new THREE.LineSegments( lineGeometry, material );

    o.isIdle = false;

    this.data.three.objs.rings.push(o);
    this.data.three.system.add(o);

    return o;
}

/**
 * Reset the scene
 */
ModuleBattleReport_SubmoduleSystem.prototype.ResetScene = function() {
    this.data.three.system.rotation.x = -Math.PI/2;
    this.data.three.system.position.y = 0;

    this.data.three.target.visible = false;
    for(var i in this.data.three.objs ) {
        for(var i2 in this.data.three.objs[i] ) {
            this.data.three.objs[i][i2].visible = false;
            this.data.three.objs[i][i2].isIdle = true;
        }  
    }
         
}






























/**
 * ======================================================================
 */




 /**
  * Handle the actual on-location fight
  */
function ModuleBattleReport_SubmoduleLocation(parent) {
    this.isActive = false;
    this.scene = false;
    this.parent = parent;
    this.data = {
        three: {
            mats: {},
            objs: {
                ships: [],
                warpins: [],
                weapons: []
            },
            uniforms: {
                time: { value: 1.0 }
            },
            scaleFactor: 1
        },
        sceneRadius: 800      //The hypothetical size of our view canvas
    };  
    this.const = {
        SYSTEM_WIDTH: 30,           //A constant for the scale of the scene
        WARPIN_TIME: 2000,          //How many ms does it take for a layer to warp in, roughly
        WARPIN_ANIMATION_TIME: 100, //How long the "poof" animation lasts
        TIME_DIALATION_FACTOR: 0.01,//Total-duration multiplier
        BATTLE_TIME_PAD_MS: 1000,   //Padding ms before and after the battle
        COLORS: {
            SIDEA: 0xaaffaa,
            SIDEB: 0xaaaaff
        },
        SHIP_LOOKUP: { //1 T1, 2 T2, 3 T3, 4 Navy, 5 Factional
            0: {file: 'unknown',type:1,size:10}, //unknown
            25: {file:'frigate',type:1,size:1},
            26: {file:'cruiser',type:1,size:3},
            27: {file:'battleship',type:1,size:5},
            29: {file:'pod',type:1,size:0},
            30: {file:'titan',type:1,size:8},
            324: {file:'frigate',type:2,size:1},
            358: {file:'cruiser',type:2,size:3},
            419: {file:'battlecruiser',type:4,size:4},
            420: {file:'destroyer',type:1,size:2},
            463: {file:'mining_barge',type:1,size:2},
            485: {file:'dreadnaught',type:1,size:6},
            540: {file:'battlecruiser',type:2,size:4},
            541: {file:'destroyer',type:2,size:2},
            547: {file:'carrier',type:1,size:6},
            659: {file:'supercarrier',type:1,size:7}, 
            831: {file:'frigate',type:2,size:1},
            832: {file:'cruiser',type:1,size:3},
            833: {file:'cruiser',type:2,size:3},
            834: {file:'frigate',type:2,size:1}, //stealth bomber
            893: {file:'frigate',type:2,size:1},
            894: {file:'cruiser',type:2,size:3},
            900: {file:'battleship',type:2,size:5},
            906: {file:'cruiser',type:2,size:3},
            963: {file:'cruiser',type:3,size:3},
            1201: {file:'battlecruiser',type:2,size:4},
            1305: {file:'destroyer',type:3,size:2},
            1527: {file:'frigate',type:2,size:1},
            1534: {file:'destroyer',type:2,size:2},
            1538: {file:'fax',type:1,size:7},
            1657: {file:'citadel',type:1,size:9}, //Citadel
            1972: {file:'cruiser',type:5,size:3},

        }
    } 
    
    
}


/**
 * 
 */
ModuleBattleReport_SubmoduleLocation.prototype.Run = function(lastTS) {
    if ( !this.isActive ) return;
    //

}

/**
 * 
 */
ModuleBattleReport_SubmoduleLocation.prototype.Resize = function() {
    if ( !this.isActive ) return;
    //
    this.timeline.Resize();
}

/**
 * Initialize with system data and battle report
 */
ModuleBattleReport_SubmoduleLocation.prototype.Init = function(sysref,br,onComplete) {
    //First-run initializations
    if ( !this.data.isInitialized ) 
        this.InitOnce(sysref,br);

    //Store for later
    var self = this;
    this.data.sysref = sysref;
    this.data.br = br;   

    console.log("ModuleBattleReport_SubmoduleLocation",sysref,br);

    //Establish sides
    this.data.sideLookup = {};
    var sindex = 0;
    for(var i in br.data.sides) {
        this.data.sideLookup[i] = sindex;
        sindex++;
    }   
    //console.log(this.data.sideLookup);
    
    //Add ships
    var numChars = 0;
    for(var i in br.data.chars) {
        var c = br.data.chars[i];
        var side = c.side ? this.data.sideLookup[c.side] : false; //temp
        var ship_id = c.ship_id ? c.ship_id : 0;

        if ( c.side == "n" ) {
            console.log("Neutral detected",c,i);
            continue; //Couldn't determine side properly - backend marked as neutral
        }

        var shipref = br.data.ships[ship_id];
        var ship_gid = 0;
        if ( shipref ) {
            ship_gid = shipref.gid;
        } 
        var icon = this.const.SHIP_LOOKUP[ship_gid];
        if ( !icon ) {
            console.log("Unknown icon type",c.ship_id,ship_gid);
            continue;
        }               
        var mat = this.data.three.mats["icon_" + ship_gid + "_" + side];
        var obj = this.GetSprite("ships",mat);
        obj.position.set(-1000,-1000,-1000); //Off-screen at first
        obj.visible = true;

        //Store some meta information
        obj.meta = {
            side: side,
            ship_gid: ship_gid,
            character_id: i,
            size: icon.size
        };

        var ss = 1/20;
        obj.scale.set(ss,ss,ss);
        numChars++;
    }

    //Organize
    
    var sortedShips = this.SortShips();
    var startTime = this.data.br.data.events[0].ts;
    var endTime = this.data.br.data.events[this.data.br.data.events.length-1].ts;

    //Presentation animation
    

    self.Animate_WarpIn_All(function() {
        var battleDurationMs = self.Animate_Battle();

        //Animate timeline        
        var timelineAnimate = {
            r: 0
        };
        new TWEEN.Tween(timelineAnimate)
            .to({r: 1},battleDurationMs)
            .easing(TWEEN.Easing.Linear.None)
            .onUpdate(function() {
                self.timeline.SetRatio(timelineAnimate.r);
            })
            .start();

        //Battle complete - exit mode
        setTimeout(function() {
            console.log("ModuleBattleReport_SubmoduleLocation.prototype.Init","complete");
            onComplete();
        },battleDurationMs + self.const.BATTLE_TIME_PAD_MS);
    });

}

/**
 * Initialization to do once
 */
ModuleBattleReport_SubmoduleLocation.prototype.InitOnce = function(sysref,br) {

    this.timeline = new Timeline();
    this.timeline.Init("#br_container_br .hud.bottom");
    this.timeline.Resize();

    //Set up system object handler
    this.data.three.location = new THREE.Object3D();    
    this.scene.add(this.data.three.location);

    this.data.three.location.rotation.x = -Math.PI/2;
    
    //Set up ship icons
    for(var i in this.const.SHIP_LOOKUP) {
        var targetMap = new THREE.TextureLoader().load("webfiles/images/icon_"+this.const.SHIP_LOOKUP[i].file+".png");

        //Side 0/A
        this.data.three.mats["icon_" + i + "_0"] = new THREE.SpriteMaterial({ 
            map: targetMap, 
            color: this.const.COLORS.SIDEA,
            sizeAttenuation: false,
            transparent: true
        });  

        //Side 1/B
        this.data.three.mats["icon_" + i + "_1"] = new THREE.SpriteMaterial({ 
            map: targetMap, 
            color: this.const.COLORS.SIDEB,
            sizeAttenuation: false,
            transparent: true
        });       
    }

    //Additional sprites/materials
    //---------------

    //Warp-in poof
    var poofMap = new THREE.TextureLoader().load("webfiles/images/sprite_warpin.png");
    this.data.three.mats.sprite_warpin = new THREE.SpriteMaterial({ 
        map: poofMap, 
        color: 0xffffff,
        sizeAttenuation: false,
        transparent: true
    }); 

    //Weapons material 
    this.data.three.mats.weaponsMat = new THREE.ShaderMaterial( {
        uniforms: this.data.three.uniforms,
        vertexShader: document.getElementById( 'br_vert_planet' ).textContent,
        fragmentShader: document.getElementById( 'br_frag_planet' ).textContent
    } );




    this.data.isInitialized = true;
    this.isActive = true;
}


/**
 * Creates/fetches an object
 */
ModuleBattleReport_SubmoduleLocation.prototype.GetSprite = function(type,material) {

    //Look for idle
    var o;
    for(var i in this.data.three.objs[type] ) {
        o = this.data.three.objs[type][i];
        if ( o.isIdle ) {
            o.isIdle = false;
            o.visible = true;
            o.name = type + "_" + i;
            o.material = material;
            return o;
        }
    }

    o = new THREE.Sprite(material); 
    o.isIdle = false;

    this.data.three.objs[type].push(o);
    this.data.three.location.add(o);

    return o;
}

/**
 * Creates/fetches an object
 */
ModuleBattleReport_SubmoduleLocation.prototype.GetQuad = function(type,material) {

    //Look for idle
    var o;
    for(var i in this.data.three.objs[type] ) {
        o = this.data.three.objs[type][i];
        if ( o.isIdle ) {
            o.isIdle = false;
            o.visible = true;
            o.name = type + "_" + i;
            o.material = material;
            return o;
        }
    }

    o = this.parent.threeLocation.NewQuad(material); 
    o.isIdle = false;

    this.data.three.objs[type].push(o);
    this.data.three.location.add(o);

    return o;
}

/**
 * Reset the scene
 */
ModuleBattleReport_SubmoduleLocation.prototype.ResetScene = function() {
    this.timeline.Reset(0,0);

    for(var i in this.data.three.objs ) {
        for(var i2 in this.data.three.objs[i] ) {
            this.data.three.objs[i][i2].visible = false;
            this.data.three.objs[i][i2].isIdle = true;
        }   
    }
        
    console.log("ModuleBattleReport_SubmoduleLocation.prototype.ResetScene");    
}

/**
 * Fetches a ship object by character ID
 * 
 * @param cid int
 *   Character ID
 * 
 * @returns THREE.Object3D
 *   Ship object
 */
ModuleBattleReport_SubmoduleLocation.prototype.GetShipByCharacterId = function(cid) {
    for(var i in this.data.three.objs.ships ) {
        o = this.data.three.objs.ships[i];
        //console.log("cid",o.meta.character_id, cid);
        if ( o.meta.character_id == cid ) {
            return o;
        }
    }
    return null;
}

/**
 * Generates sorted/organized ship lists
 */
ModuleBattleReport_SubmoduleLocation.prototype.SortShips = function() {
    
    //Pre sort ships by size
    var presort = [[],[]];
    for(var i in this.data.three.objs.ships ) {
        o = this.data.three.objs.ships[i];
        if ( !o.isIdle ) {
            var shipData = this.const.SHIP_LOOKUP[o.meta.ship_gid];
            if ( shipData || shipData.size ) {
                var size = shipData.size;
                presort[o.meta.side].push({size:size,ship:o});
            } else {
                console.log("Ship data not found",o.meta,shipData);
            }
        }
    }
    for(var side in presort ) {  
        presort[side].sort(function(a, b){return a.size - b.size}).reverse();
    }
    

    //Go throgh all non-idle ships
    for(var side in presort ) {  
        var count = 0;
        for(var i in presort[side] ) {     

            var layerWidth = 1 + Math.floor(Math.log(presort[side].length)) * 7;

            presort[side][i].ship.meta.layer = Math.floor(count / layerWidth);
            presort[side][i].ship.meta.layerIndex = count % layerWidth;
            count++;
        }
    }

}

/**
 * Set up the animation for all ships
 */
ModuleBattleReport_SubmoduleLocation.prototype.Animate_WarpIn_All = function(onComplete) {
    var latestWarpComplete = 0;
    for(var i in this.data.three.objs.ships ) {
        o = this.data.three.objs.ships[i];
        if ( !o.isIdle ) {
            var time = this.Animate_WarpIn(o);
            if ( time > latestWarpComplete ) {
                latestWarpComplete = time;
            }
        }
    }

    //Wait until the last warp-in has completed, then complete
    setTimeout(function() {
        onComplete();
    },latestWarpComplete);
}

/**
 * Warp-in an individual ship
 */
ModuleBattleReport_SubmoduleLocation.prototype.Animate_WarpIn = function(ship) {
    var self = this;
    var dir = ship.meta.side % 2 == 0 ? -1 : 1; //Which direction is it warping in from?

    //var zOffset = (ship.meta.layerIndex % 2 == 1 ? ship.meta.layerIndex * 1.5 : ship.meta.layerIndex * -1.5);
    var zOffset = 1.8 * ( (ship.meta.layerIndex % 2 == 1) ? (( ship.meta.layerIndex + 1 ) / 2) : (-ship.meta.layerIndex / 2) );
    var zVary = 0;//0.25 * (Math.random() * 2 - 1);
    var xVary = Math.random() * 4;
    var delay = (Math.random() * 150) + (ship.meta.layer*333);

    //Move system model
    var animate = {
        x: 100*dir * this.const.SYSTEM_WIDTH + (Math.random() * 1.5) ,
        z: zOffset * this.const.SYSTEM_WIDTH      
    };
    var dest = {
        x: ((dir * ship.meta.layer * 2.25) + (dir*5)) * this.const.SYSTEM_WIDTH + xVary,
        z: (zOffset + zVary) * this.const.SYSTEM_WIDTH
    };

    //console.log("WarpIn",ship.meta.layer,ship.meta.layerIndex,animate,dest);

    var warpin_time = this.const.WARPIN_TIME * 0.75;

    if ( ship.meta.size < 8 ) {

        //Warp ship in
        new TWEEN.Tween(animate)
            .to(dest,warpin_time)
            .delay(delay) //Stagger them a little
            .easing(TWEEN.Easing.Exponential.Out)
            .onUpdate(function() {
                ship.position.set(animate.x,0,animate.z);
            })
            .start();

        //Warp-in "poof"
        setTimeout(function() {

            var warpin = self.GetSprite('warpins',self.data.three.mats["sprite_warpin"]);
            var scale = 0.08;
            warpin.scale.set(scale,scale,scale);
            warpin.position.set(ship.position.x, -1, ship.position.z);

            var animatePoof = {o:0.75};
            var animatePoofEnd = {o: 0};
            new TWEEN.Tween(animatePoof)
                .to(animatePoofEnd,self.const.WARPIN_ANIMATION_TIME)
                .easing(TWEEN.Easing.Exponential.Out)
                .onUpdate(function() {
                    warpin.material.opacity = animatePoof.o;
                })
                .onComplete(function() {
                    warpin.visible = false;
                    warpin.isIdle = true;
                })
                .start();

        },warpin_time * (0.25 + (Math.random()*0.05)) + delay);    

    } else {
        //It's a station!
        ship.position.set(dest.x,0,dest.z);
    }  

    return delay + warpin_time;
}



/**
 * Script out the entire battle
 * 
 * @return Number
 *   The number of milliseconds the battle is expected to run
 */
ModuleBattleReport_SubmoduleLocation.prototype.Animate_Battle = function() {
    var self = this;


    //Timing
    var startTime = this.data.br.data.events[0].ts;
    var endTime = this.data.br.data.events[this.data.br.data.events.length-1].ts;
    self.timeline.Reset(startTime,endTime);

    var timeDialation = this.const.TIME_DIALATION_FACTOR;
    var duration = endTime - startTime;
    var hastenedDuration = 1000 * (duration * timeDialation);

    console.log("Timing",duration,hastenedDuration);

    //Preprocess
    //for(var i in this.data.br.events) {
    //    var e = this.data.br.events[i];        
    //}

    //Animate
    var alreadyKilled = [];
    for(var i in this.data.br.data.events) {
        var e = this.data.br.data.events[i];        
        var r = (e.ts - startTime) / duration;
        var eventStart = r * hastenedDuration;

        //console.log("Event",e.type,e.victim,i,r,eventStart);

        switch(e.type) {
            case "arrive":
            break;
            case "kill":
               var ship = self.GetShipByCharacterId(e.victim);
               if ( ship ) {

                    //If someone was already killed, skip 
                    //@todo make this pod-only (hand re-shipping)
                    if ( !alreadyKilled.includes(e.victim) ) {
                        alreadyKilled.push(e.victim);
                        self.timeline.AddEvent(e.ts,1);
                    } else {
                        console.log("skipping repeat kill",e.victim);
                    }

                   self.Animate_ExplodeShip(ship,eventStart);
               }
            break;
        }        
    }

    self.timeline.Redraw();

    return hastenedDuration;
}

/**
 * 
 */
ModuleBattleReport_SubmoduleLocation.prototype.Animate_ExplodeShip = function(ship,delay) {
    setTimeout(function() {                    
        //console.log("ship destroy",e.victim,ship);
    
        ship.isIdle = true;
        ship.visible = false;
        //@todo explode call
        
    }, delay);
}

























/**
 * A visual timeline widget
 */
function Timeline() {
    this.data = {
        events: [],
        tss: 0,
        tse: 0
    };
    this.const = {
        EVENT_TYPE_SIDEA: 1,
        EVENT_TYPE_SIDEB: 2      
    };
}

/**
 * Event type Constants
 */


/**
 * Initialize timeline structure, appending to element selector.
 * 
 * @param element string
 *   The selector for the container this is being appended to.
 * 
 * @returns this
 */
Timeline.prototype.Init = function(element) {
    this.data.$wrapper = $("<div class='timeline-wrapper'><div class='timeline-date'></div><div class='timeline-bar'><div class='timeline-head'><div class='timeline-head-graphic'></div></div><canvas class='timeline-events'></canvas></div></div>");
    $(element).append(this.data.$wrapper);
    this.data.$head = $(".timeline-head",this.data.$wrapper);
    this.data.$date = $(".timeline-date",this.data.$wrapper);

    this.data.eventsCanvas = $(".timeline-events",this.data.$wrapper)[0];
    this.data.ctx = this.data.eventsCanvas.getContext('2d');
    return this;
}

/**
 * Sets the current time and progress through the time span
 * 
 * @param r number
 *   The ratio through the event, 0 through 1
 * @param tss number
 *   Timestamp of beginning of event (in seconds)
 * @param tse number
 *   Timestamp of the end of the event (in seconds)
 * 
 * @returns this
 */
Timeline.prototype.SetRatio = function(r) {
    var now = Math.floor(THREE.Math.lerp(this.data.tss,this.data.tse,r));
    var date = new Date(now * 1000).toUTCString();

    $(this.data.$date).text(date);
    $(this.data.$head).css("left",(100*r) + "%");

    return this;
}

/**
 * Adds an event to the timeline
 * 
 * @param ts number
 *   The timestamp (seconds) of when the timeline happened
 * @param type string
 *   The type of event
 *     - a : Side A kill
 *     - b : Side B kill
 */
Timeline.prototype.AddEvent = function(ts,type) {
    this.data.events.push({ ts:ts, type:type });
    return this;
} 


/**
 * Resets the timeline attached events
 * 
 * @param tss number
 *   Time span start time (seconds)
 * @param tse number
 *   Time span end time (seconds) * 
 *
 */
Timeline.prototype.Reset = function(tss,tse) {
    this.data.tss = tss;
    this.data.tse = tse;
    this.data.events = [];
    $(this.data.$wrapper).hide();
    this.Redraw();
    return this;
}

/**
 * Redraws the canvas/events
 */
Timeline.prototype.Redraw = function() {
    var duration = this.data.tse - this.data.tss;
    this.data.ctx.clearRect(0,0,this.data.ctx.width,this.data.ctx.height);

    //Draw each event
    for(var i in this.data.events ) {
        var e = this.data.events[i];      

        var r = (e.ts - this.data.tss ) / duration;
        var xpos = r * this.data.ctx.width;

        //console.log("draw",e.ts,this.data.tse,duration,xpos);

        switch(e.type) {
            default:
                this.data.ctx.fillStyle = "rgba(255,255,255,0.5)";
            break;
        }

        this.data.ctx.fillRect(xpos,0,1,this.data.ctx.height);

    }

    $(this.data.$wrapper).show();

    return this;
}

/**
 * Does a resize event
 */
Timeline.prototype.Resize = function() {
    var cw = $(this.data.$wrapper).width();
    var ch = $(this.data.$wrapper).height();
    this.data.eventsCanvas.width = cw;
    this.data.eventsCanvas.height = ch;
    this.data.ctx.width = cw;
    this.data.ctx.height = ch;
}