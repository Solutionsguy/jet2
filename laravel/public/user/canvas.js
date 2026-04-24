//CANVAS ANIMTION FUNCTION
/**
 * Scaling for high-pixel-density mobile screens
 */
var pixelRatio = window.devicePixelRatio || 1;
var cW = $('.stage-board').width();
var cH = $('.stage-board').height();

// Global animation frame references
var requestID = null;
var stopPlaneRequestID = null;

var canvas = $('#myCanvas');
var ctx = canvas[0].getContext('2d', { alpha: true });

// Scale canvas element for sharpness without breaking layout math
function scaleCanvas() {
    var canvasElement = $('#myCanvas')[0];
    var logicalW = $('.stage-board').width();
    var logicalH = $('.stage-board').height();
    
    // Set display size (logical pixels)
    canvasElement.style.width = logicalW + 'px';
    canvasElement.style.height = logicalH + 'px';
    
    // Set actual drawing size (physical pixels)
    canvasElement.width = logicalW * pixelRatio;
    canvasElement.height = logicalH * pixelRatio;
    
    // Scale all drawing operations globally
    ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
    
    // Update global logical dimensions for math
    cW = logicalW;
    cH = logicalH;
}

scaleCanvas();

var screenHeight = $(window).height() - 4;
var screenWidth = $(window).width();
var x = 0;

var canvasHeight = 0;
var canvasWidth = 0;
var calcwidth = 0;
var calcheight = 0;
var horizontalLine = 0;
var verticalLine = 0;
var verticaldots = 0;
var verticalDotSize = 0;
var boardWidth = 0;
var boardheight = 0;
var widthDouble = 0;
var xPoint = 0;
var yPoint = 0;
var diffx = 0;
var imgheight = 0;
var imgwidth = 0;
var imgyposition = 0;
var imgxposition = 0;
var settimeinterval = 0;
var checkuplinedownlinecount = 0;
var diffy = 0;
var diffx1 = 0;

var yend = 0;
var xend = 0;
var backgroundImage = '';
var start = null;
var progress = 0;
var frameIndex = 0;
var countInterval = 0;
var estimateHeight = 0;
var estimateWidth = 0;
var HorizontalDotsCountRun = 0;
var VerticalDotsCountRun = 0;
var lastUpdate = Date.now();
var y0 = 0;
var x0 = 0;
var y1 = 0;
var x1 = 0;
var y2 = 0;
var x2 = 0;
var intervalID;
var intervalID1;
var stopPlaneEvent = 0;
var nx0 = 0;
var ny0 = 0;
var nx1 = 0;
var ny1 = 0;
var nx2 = 0;
var ny2 = 0;
var StopPlaneIntervalID1 = 0;
var startupdown = 0;
var imgTag;
let bmp;

/**
 * Initialize canvas variables without starting animation
 */
function initializeCanvasVariables() {
    // 1. Maintain Sharpness
    scaleCanvas();
    
    screenHeight = $(window).height() - 4;
    screenWidth = $(window).width();
    x = 0;

    // 2. Use Logical dimensions (cW/cH) for all layout math
    canvasHeight = cH;
    canvasWidth = cW;
    calcwidth = canvasWidth / 100;
    calcheight = canvasHeight / 100;
    
    // CRITICAL FIX: Use logical width (cW) for mobile check
    if (cW < 992) {
        diffx = calcwidth * 45;
        horizontalLine = calcwidth * 10;
        verticalLine = calcheight * 10;
        
        // Logical Mobile Plane Size
        imgheight = 48;
        imgwidth = 200;
        imgyposition = 45;
        imgxposition = 10;
        
        // Use HD assets if available, but draw at logical size
        imgTag = new Image();
        imgTag.src = (pixelRatio >= 2) ? "./images/sprite3.png" : "./images/sprite2.png";
        
        settimeinterval = 40;
        checkuplinedownlinecount = 50;
    } else {
        diffx = calcwidth * 30;
        horizontalLine = calcwidth * 5;
        verticalLine = calcheight * 5;
        
        // Logical Desktop Plane Size
        imgheight = 71;
        imgwidth = 300;
        imgyposition = 66;
        imgxposition = 15;
        
        imgTag = new Image();
        imgTag.src = "./images/sprite3.png";
        
        settimeinterval = 20;
        checkuplinedownlinecount = 150;
    }

    verticaldots = verticalLine / 100;
    verticalDotSize = (verticaldots * 50);
    boardWidth = canvasWidth;
    boardheight = canvasHeight;
    widthDouble = boardWidth * 2.5;
    xPoint = 0 - (boardWidth * 1.25);
    yPoint = boardheight - (boardWidth * 1.25);
    $(".rotateimage").css("width", widthDouble).css("height", widthDouble).css("top", yPoint).css("left", xPoint);
    
    diffy = calcheight * 70;
    diffx1 = canvasWidth - (calcwidth * 60);

    yend = canvasHeight - diffy;
    xend = canvasWidth - diffx;
    backgroundImage = '';
    start = null;
    progress = 0;
    frameIndex = 0;
    countInterval = 0;
    estimateHeight = 0;
    estimateWidth = 0;
    HorizontalDotsCountRun = 1;
    VerticalDotsCountRun = 1;
    lastUpdate = Date.now();
    y0 = (cH - verticalLine);
    x0 = verticalLine;
    y1 = (cH - verticalLine);
    x1 = diffx1;
    y2 = yend;
    x2 = xend;
    startupdown = 0;
}

setVariable();
function setVariable(is_plan = '') {
    isStopPlaneAnimationRunning = false;
    if (stopPlaneRequestID) { window.cancelAnimationFrame(stopPlaneRequestID); stopPlaneRequestID = null; }
    
    if (intervalID) { window.clearInterval(intervalID); intervalID = null; }
    if (intervalID1) { window.clearInterval(intervalID1); intervalID1 = null; }
    
    stopPlaneEvent = 1;
    if (requestID) { window.cancelAnimationFrame(requestID); requestID = null; }
    
    initializeCanvasVariables();
    $(".rotateimage").addClass('rotatebg');
    stopPlaneEvent = 0;
   
    var is_plan_display = (is_plan != '') ? imgTag : '';
    animatePathDrawing(ctx, verticalLine, (cH - verticalLine), diffx1, (cH - verticalLine), xend, yend, 5000, is_plan_display);
}

function animatePathDrawing(ctx, x0, y0, x1, y1, x2, y2, duration, imgTag) {
    start = null;
    var step = function(timestamp) {
        if (stopPlaneEvent === 1) return;
        if (start === null) start = timestamp;
        var progress = Math.min((timestamp - start) / duration, 1);

        if (imgTag != '') {
            drawBezierSplit(ctx, x0, y0, x1, y1, x2, y2, 0, progress, imgTag);
        }

        if (progress < 1 && stopPlaneEvent === 0) {
            requestID = window.requestAnimationFrame(step);
        }
    };
    requestID = window.requestAnimationFrame(step);
}

var isStopPlaneAnimationRunning = false;

function stopPlane() {
    if(StopPlaneIntervalID1 == 0){
        if (requestID) { window.cancelAnimationFrame(requestID); requestID = null; }
        if (intervalID) { clearInterval(intervalID); intervalID = null; }
        if (intervalID1) { clearInterval(intervalID1); intervalID1 = null; }
        
        stopPlaneEvent = 1;
        isStopPlaneAnimationRunning = true;
        $(".rotateimage").removeClass('rotatebg');

        ctx.clearRect(0, 0, cW, cH);
        var intervalTimex = 100;
        var intervalTimey = 50;

        if (startupdown == 1) {
            nx2 = estimateWidth;
            ny2 = estimateHeight;
        }
        var stopPlaneCount = Math.round((cW - nx2) / 4);

        var flyStep = () => {
            if (!isStopPlaneAnimationRunning) return;
            ctx.beginPath();
            ctx.clearRect(0, 0, cW, cH);
            ctx.moveTo(nx0, ny0);
            ctx.quadraticCurveTo(nx1, ny1, nx2 + intervalTimex, ny2 - intervalTimey);
            GameObject(imgTag, (nx2 + intervalTimex) - imgxposition, (ny2 - intervalTimey) - imgyposition, imgwidth, imgheight, 100, 2);
            ctx.closePath();
            
            StopPlaneIntervalID1++;
            intervalTimex += 4;
            intervalTimey += 1;

            if (StopPlaneIntervalID1 < stopPlaneCount) {
                stopPlaneRequestID = window.requestAnimationFrame(flyStep);
            } else {
                isStopPlaneAnimationRunning = false;
                stopPlaneRequestID = null;
                StopPlaneIntervalID1 = 0;
            }
        };
        stopPlaneRequestID = window.requestAnimationFrame(flyStep);
    }
}

function drawLine() {
    ctx.beginPath();
    ctx.moveTo(verticalLine, 0);
    ctx.lineTo(verticalLine, (cH - verticalLine));
    ctx.lineTo(cW, (cH - verticalLine));
    ctx.lineWidth = 1;
    ctx.strokeStyle = '#423033';
    ctx.stroke();
    ctx.closePath();
}

function drawHorizontalDots() {
    var horizontalLinedata = (cW < 992) ? horizontalLine / 2 : horizontalLine;
    ctx.save();
    ctx.beginPath();
    ctx.rect(verticalLine, (cH - verticalLine), cW, verticalLine);
    ctx.closePath();
    ctx.clip();
    for (let i = 0; i < 20; i++) {
        ctx.beginPath();
        ctx.arc(((horizontalLinedata * 2) * i) + 3, (cH - verticalLine) + verticalDotSize, 2, 0, 2 * Math.PI);
        ctx.fillStyle = 'white';
        ctx.fill();
        ctx.closePath();
    }
    ctx.restore();
}

function animationHorizontalDots() {
    var horizontalLinedata = (cW < 992) ? horizontalLine / 2 : horizontalLine;
    ctx.save();
    ctx.beginPath();
    ctx.fillStyle = "rgba(0,0,0,0.1)";
    ctx.rect(verticalLine, (cH - verticalLine), cW, verticalLine);
    ctx.fill();
    ctx.closePath();
    ctx.clip();
    for (let i = 0; i < 2000; i++) {
        ctx.beginPath();
        ctx.arc((((horizontalLinedata * 2) * i) + 3) - HorizontalDotsCountRun, (cH - verticalLine) + verticalDotSize, 2, 0, 2 * Math.PI);
        ctx.fillStyle = 'white';
        ctx.fill();
        ctx.closePath();
    }
    HorizontalDotsCountRun += 3;
    ctx.restore();
}

function animationVerticalDots() {
    var verticalLinedata = (cW < 992) ? verticalLine / 2 : verticalLine;
    ctx.save();
    ctx.beginPath();
    ctx.fillStyle = "rgba(0,0,0,0.1)";
    ctx.rect(0, 0, verticalLine, (cH - verticalLine));
    ctx.closePath();
    ctx.clip();
    for (let i = 0; i < 2000; i++) {
        ctx.beginPath();
        ctx.arc((verticalLine - verticalDotSize), ((cH - (verticalLinedata * i)) * 2 - 5) + VerticalDotsCountRun, 2, 0, 2 * Math.PI);
        ctx.fillStyle = '#ff0647';
        ctx.fill();
        ctx.closePath();
    }
    VerticalDotsCountRun += 3;
    ctx.restore();
}

function drawVerticalDots() {
    var verticalLinedata = (cW < 992) ? verticalLine / 2 : verticalLine;
    ctx.save();
    ctx.beginPath();
    ctx.rect(0, 0, verticalLine, (cH - verticalLine));
    ctx.closePath();
    ctx.clip();
    for (let i = 0; i < 20; i++) {
        ctx.beginPath();
        ctx.arc((verticalLine - verticalDotSize), (verticalLinedata * i) * 2 + 5, 2, 0, 2 * Math.PI);
        ctx.fillStyle = '#ff0647';
        ctx.fill();
        ctx.closePath();
    }
    ctx.restore();
}

/**
 * Enhanced draw function to handle high-res source vs logical dest
 */
function draw(spritesheet, x, y, destW, destH, frameIndex, numberOfFrames) {
    // Use natural dimensions for accuracy
    var sw = spritesheet.naturalWidth || (spritesheet.src.includes('sprite3.png') ? 300 : 200);
    var sh = spritesheet.naturalHeight || (spritesheet.src.includes('sprite3.png') ? 71 : 48);
    
    // Single frame width from source
    var frameW = sw / numberOfFrames;
    
    ctx.drawImage(
        spritesheet, 
        (frameIndex * frameW), 0, frameW, sh, // Source (x, y, w, h)
        x, y, destW, destH                   // Destination (x, y, w, h)
    );
}

function GameObject(spritesheet, x, y, width, height, timePerFrame, numberOfFrames) {
    numberOfFrames = numberOfFrames || 1;
    if (Date.now() - lastUpdate >= timePerFrame) {
        frameIndex++;
        if (frameIndex >= numberOfFrames) frameIndex = 0;
        lastUpdate = Date.now();
    }
    
    // Calculate actual destination width for one frame
    var singleFrameDestW = width / numberOfFrames;
    
    ctx.save();
    draw(spritesheet, x, y, singleFrameDestW, height, frameIndex, numberOfFrames);
    
    // Tint the single frame
    ctx.globalCompositeOperation = 'source-atop';
    ctx.fillStyle = '#ff0647';
    ctx.fillRect(x, y, singleFrameDestW, height);
    ctx.restore();
}

function drawBezierSplit(ctx, x0, y0, x1, y1, x2, y2, t0, t1, imgTag) {
    if (stopPlaneEvent === 1) return;
    ctx.beginPath();
    ctx.clearRect(0, 0, cW, cH);
    drawLine();
    animationHorizontalDots();
    animationVerticalDots();

    if (0.0 == t0 && t1 == 1.0) {
        startupdown = 1;
        ctx.moveTo(x0, y0);
        ctx.quadraticCurveTo(x1, y1, x2, y2);
        GameObject(imgTag, x2 - imgxposition, y2 - imgyposition, imgwidth, imgheight, 100, 2);
        ctx.lineWidth = 5;
        ctx.strokeStyle = '#ff0647';
        ctx.stroke();
        ctx.closePath();
        fillShape(x2, y2, x0, y0, x1, y1, t1);
        startfirstinterval();
    } else {
        var t01 = 1.0 - t0, t02 = t01 * t01, t03 = 2.0 * t0 * t01;
        nx0 = t02 * x0 + t03 * x1 + (t0 * t0) * x2;
        ny0 = t02 * y0 + t03 * y1 + (t0 * t0) * y2;
        var t11 = 1.0 - t1, t12 = t11 * t11, t13 = 2.0 * t1 * t11;
        nx2 = t12 * x0 + t13 * x1 + (t1 * t1) * x2;
        ny2 = t12 * y0 + t13 * y1 + (t1 * t1) * y2;
        nx1 = lerp(lerp(x0, x1, t0), lerp(x1, x2, t0), t1);
        ny1 = lerp(lerp(y0, y1, t0), lerp(y1, y2, t0), t1);
        ctx.moveTo(nx0, ny0);
        ctx.quadraticCurveTo(nx1, ny1, nx2, ny2);
        GameObject(imgTag, nx2 - imgxposition, ny2 - imgyposition, imgwidth, imgheight, 100, 2);
        ctx.lineWidth = 5;
        ctx.strokeStyle = '#ff0647';
        ctx.stroke();
        ctx.closePath();
        fillShape(nx2, ny2, nx0, ny0, nx1, ny1, 0);
    }
}

function startfirstinterval() {
    if (intervalID) window.clearInterval(intervalID);
    if (stopPlaneEvent === 1) return;
    intervalID = setInterval(() => {
        if (stopPlaneEvent === 1) { clearInterval(intervalID); return; }
        downplane(x0, y0, x1, y1, x2, y2);
        if (++countInterval >= checkuplinedownlinecount) {
            clearInterval(intervalID); intervalID = null; countInterval = 0; startsecondinterval();
        }
    }, settimeinterval);
}

function startsecondinterval() {
    if (intervalID1) window.clearInterval(intervalID1);
    if (stopPlaneEvent === 1) return;
    intervalID1 = setInterval(() => {
        if (stopPlaneEvent === 1) { clearInterval(intervalID1); return; }
        upplane(x0, y0, x1, y1, x2, y2);
        if (++countInterval >= checkuplinedownlinecount) {
            clearInterval(intervalID1); intervalID1 = null; countInterval = 0; startfirstinterval();
        }
    }, settimeinterval);
}

function upplane(x0, y0, x1, y1, x2, y2) {
    ctx.beginPath(); ctx.clearRect(0, 0, cW, cH);
    drawLine(); animationHorizontalDots(); animationVerticalDots();
    var IncreaseY = estimateHeight - countInterval;
    var DecreaseX = estimateWidth - countInterval;
    ctx.moveTo(x0, y0); ctx.quadraticCurveTo(x1, y1, DecreaseX, IncreaseY);
    GameObject(imgTag, DecreaseX - imgxposition, IncreaseY - imgyposition, imgwidth, imgheight, 100, 2);
    ctx.lineWidth = 5; ctx.strokeStyle = '#ff0647'; ctx.stroke(); ctx.closePath();
    ctx.beginPath(); ctx.moveTo(x0, y0); ctx.quadraticCurveTo(x1, y1, DecreaseX, IncreaseY);
    ctx.lineTo(DecreaseX + 3, IncreaseY); ctx.lineTo(DecreaseX, y0);
    ctx.fillStyle = "rgba(255,6,71,0.35)"; ctx.fill(); ctx.closePath();
}

function downplane(x0, y0, x1, y1, x2, y2) {
    ctx.beginPath(); ctx.clearRect(0, 0, cW, cH);
    drawLine(); animationHorizontalDots(); animationVerticalDots();
    var DecreaseY = y2 + countInterval;
    var IncreaseX = x2 + countInterval;
    estimateHeight = DecreaseY; estimateWidth = IncreaseX;
    ctx.moveTo(x0, y0); ctx.quadraticCurveTo(x1, y1, IncreaseX, DecreaseY);
    GameObject(imgTag, IncreaseX - imgxposition, DecreaseY - imgyposition, imgwidth, imgheight, 100, 2);
    ctx.lineWidth = 5; ctx.strokeStyle = '#ff0647'; ctx.stroke(); ctx.closePath();
    ctx.beginPath(); ctx.moveTo(x0, y0); ctx.quadraticCurveTo(x1, y1, IncreaseX, DecreaseY);
    ctx.lineTo(IncreaseX + 3, DecreaseY); ctx.lineTo(IncreaseX, y0);
    ctx.fillStyle = "rgba(255,6,71,0.35)"; ctx.fill(); ctx.closePath();
}

function lerp(v0, v1, t) { return (1.0 - t) * v0 + t * v1; }

function fillShape(nx2, ny2, nx0, ny0, nx1, ny1, t1) {
    ctx.beginPath(); ctx.moveTo(nx0, ny0); ctx.quadraticCurveTo(nx1, ny1, nx2, ny2);
    ctx.lineTo(nx2 + (t1 == 1.0 ? 3 : 0), ny2);
    ctx.lineTo(nx2 + (t1 == 1.0 ? 3 : 0), y0);
    ctx.fillStyle = "rgba(255,6,71,0.35)"; ctx.fill(); ctx.closePath();
}

function startPlaneAtMultiplier(multiplier) {
    multiplier = Math.max(1.00, multiplier);
    isStopPlaneAnimationRunning = false;
    if (intervalID) { clearInterval(intervalID); intervalID = null; }
    if (intervalID1) { clearInterval(intervalID1); intervalID1 = null; }
    if (stopPlaneRequestID) { window.cancelAnimationFrame(stopPlaneRequestID); stopPlaneRequestID = null; }
    stopPlaneEvent = 1;
    initializeCanvasVariables();
    var initialCurveEndMultiplier = 1.50;
    var progressInCurve = Math.min((multiplier - 1.00) / (initialCurveEndMultiplier - 1.00), 1.0);
    stopPlaneEvent = 0;
    if (multiplier >= initialCurveEndMultiplier) {
        var extraProgress = Math.min((multiplier - initialCurveEndMultiplier) / 10.0, 0.5);
        var oscillationOffset = checkuplinedownlinecount * 0.5;
        estimateWidth = x2 + (extraProgress * oscillationOffset);
        estimateHeight = y2 + (extraProgress * oscillationOffset * 0.3);
        startupdown = 1;
        ctx.beginPath(); ctx.clearRect(0, 0, cW, cH);
        drawLine(); animationHorizontalDots(); animationVerticalDots();
        ctx.beginPath(); ctx.moveTo(x0, y0); ctx.quadraticCurveTo(x1, y1, estimateWidth, estimateHeight);
        ctx.lineWidth = 5; ctx.strokeStyle = '#ff0647'; ctx.stroke(); ctx.closePath();
        GameObject(imgTag, estimateWidth - imgxposition, estimateHeight - imgyposition, imgwidth, imgheight, 100, 2);
        fillShape(estimateWidth, estimateHeight, x0, y0, x1, y1, 1);
        countInterval = Math.floor(oscillationOffset * extraProgress);
        startfirstinterval();
    } else if (progressInCurve > 0) {
        var t = progressInCurve;
        ctx.beginPath(); ctx.clearRect(0, 0, cW, cH);
        drawLine(); drawHorizontalDots(); drawVerticalDots();
        animatePathDrawing(ctx, x0, y0, x1, y1, x2, y2, 5000, imgTag);
    }
}

function stopPlaneAnimations() {
    stopPlaneEvent = 1; isStopPlaneAnimationRunning = false;
    if (intervalID) { clearInterval(intervalID); intervalID = null; }
    if (intervalID1) { clearInterval(intervalID1); intervalID1 = null; }
    if (stopPlaneRequestID) { window.cancelAnimationFrame(stopPlaneRequestID); stopPlaneRequestID = null; }
    if (requestID) { window.cancelAnimationFrame(requestID); requestID = null; }
    countInterval = 0; startupdown = 0; start = null;
    if (ctx) ctx.clearRect(0, 0, cW, cH);
}

function resumePlaneAnimation(currentMultiplier) {
    if (!currentMultiplier || currentMultiplier < 1.00) return;
    stopPlaneAnimations();
    setTimeout(() => {
        stopPlaneEvent = 0; countInterval = 0; startupdown = 0;
        HorizontalDotsCountRun = 1; VerticalDotsCountRun = 1;
        startPlaneAtMultiplier(currentMultiplier);
    }, 50);
}

window.startPlaneAtMultiplier = startPlaneAtMultiplier;
window.stopPlaneAnimations = stopPlaneAnimations;
window.resumePlaneAnimation = resumePlaneAnimation;
