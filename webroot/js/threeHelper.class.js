/**
 * Helper class to abstract some of the Three.js functionality
 */

function ThreeHelper(container) {   
    this.clearColor = 0xff0000; 

    this.container = $(container);

	this.camera = new THREE.PerspectiveCamera( 90, 1 , 0.1, 100000 );
    this.camera.position.z = 1000;
    this.camera.position.y = 0;
    this.camera.lookAt(new THREE.Vector3(0,0,0));

    this.renderer = new THREE.WebGLRenderer({antialias: true, alpha: true });   
    $(this.container).append( this.renderer.domElement );   
    //this.renderer.autoClear = true;
    this.renderer.setClearColor( this.clearColor, 0 );

    this.Resize();
}

/**
 * When the screen resizes
 */
ThreeHelper.prototype.Resize = function(ww,wh) {
    console.log("ThreeHelper.Resize",ww,wh);

    this.renderer.setSize(ww,wh,false);
    this.renderer.setPixelRatio( window.devicePixelRatio );
    this.camera.aspect = ww/wh;
	this.camera.updateProjectionMatrix();
}

/**
 * Update objects, etc
 */
ThreeHelper.prototype.Update = function(delta) {

    
}

/**
 * Update objects, etc
 */
ThreeHelper.prototype.Render = function(scene) {
    this.renderer.render( scene, this.camera );    
}

/**
 * 
 */
ThreeHelper.prototype.Clear = function() {
    this.renderer.clear();    
}

/**
 * 
 */
ThreeHelper.prototype.ClearDepth = function() {
    this.renderer.clearDepth();    
}


// Helper functions 
// -=================================================

/**
 * Move camera to position, and look at position
 */
ThreeHelper.prototype.MoveCamera = function(toPos, lookAtPos) {
    this.camera.position.set(toPos.x,toPos.y,toPos.z);
    if ( lookAtPos != undefined )
        this.camera.lookAt(new THREE.Vector3(lookAtPos.x,lookAtPos.y,lookAtPos.z));
}

/**
 * Creates a new scene object
 */
ThreeHelper.prototype.NewScene = function() {
    scene = new THREE.Scene();
    scene.background = this.clearColor;
    return scene;
}


/**
 * Generates a new quad
 */
ThreeHelper.prototype.NewQuad = function(material) {
    quad = new THREE.Geometry(); 
    quad.vertices.push(new THREE.Vector3(0.0,  0.5, 0.0)); 
    quad.vertices.push(new THREE.Vector3( 1.0,  0.5, 0.0)); 
    quad.vertices.push(new THREE.Vector3( 1.0, -0.5, 0.0)); 
    quad.vertices.push(new THREE.Vector3(0.0, 0.5, 0.0)); 
    quad.faces.push(new THREE.Face3(0, 1, 2)); 
    quad.faces.push(new THREE.Face3(0, 3, 2));

    mesh = new THREE.Mesh( quad, material );
    return mesh;
}