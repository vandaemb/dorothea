OpenLayers.Util.onImageLoadError = function() {
    this.style.display="none"
};

function startup() {
    if (window.location.hash && window.location.hash != "#mappage") {
        $.mobile.changePage("#mappage");
    }
    fixContentHeight();
    init();
}

function fixContentHeight() {
    var footer = $("div[data-role='footer']:visible");
    var header = $("div[data-role='header']:visible");
    var content = $("div[data-role='content']:visible:visible");
    var viewHeight = $(window).height();
    var contentHeight = viewHeight - footer.outerHeight() - header.outerHeight();
    if ((content.outerHeight() + footer.outerHeight() + header.outerHeight()) !== viewHeight) {
        contentHeight -= (content.outerHeight() - content.height());
        content.height(contentHeight);
    }
    document.getElementById("map").style.height = contentHeight + "px";
}



var markerStyle = new OpenLayers.Style(
    {
        graphicWidth: 32,
        graphicHeight: 37,
        graphicYOffset: -37,
        externalGraphic: '/dorothea/img/marker.png'
    }
);

var markerLayer = new OpenLayers.Layer.Vector('marker', {
    styleMap: new OpenLayers.StyleMap(markerStyle)
});

var myLocation = new OpenLayers.Layer.Vector("My Location", {
    displayInLayerSwitcher: true
});

var geolocateCtrl = new OpenLayers.Control.Geolocate({
    id: 'locate-control',
    geolocationOptions: {
        enableHighAccuracy: true,
        maximumAge: 0,
        timeout: 5000
    }
});



function init() {

    var attributionCtrl = new OpenLayers.Control.Attribution();

    var touchnavCtrl = new OpenLayers.Control.TouchNavigation({
        dragPanOptions: {
            interval: 100,
            enableKinetic: true
        },
        pinchZoom: new  OpenLayers.Control.PinchZoom({autoActivate: true}),
        clickHandlerOptions: {
            handleSingle: function(ev){
                updateloc(ev);
            }
        }
    });

    var cacheWrite = new OpenLayers.Control.CacheWrite({
        imageFormat: "image/png",
        eventListeners: {
            cachefull: function() {
                console.log("Cache full")
            }
        }
    });

    var cacheRead = new OpenLayers.Control.CacheRead({

    });

    var dienstkaartgrijs = new OpenLayers.Layer.WMS("dienstkaart-grijs-2013",
        "/geoserver/wms?",
        {layers: 'dienstkaart-grijs-2013', transparent: true},
        {isBaseLayer: true}
    );

    var map = new OpenLayers.Map({
        projection: new OpenLayers.Projection("EPSG:31370"),
        units: "m",
        numZoomLevels: 12,
        maxResolution: 256,
        maxExtent: new OpenLayers.Bounds(
            18000,
            152999.75,
            280144,
            415143.75
        ),
        div: "map",
        theme: null,
        controls: [geolocateCtrl, attributionCtrl, touchnavCtrl,cacheWrite, cacheRead],
        layers: [dienstkaartgrijs, myLocation, markerLayer]
    });

    cacheWrite.activate();
    cacheRead.activate();
    geolocateCtrl.activate();


    geolocateCtrl.events.register("locationupdated", this, function (e) {
        myLocation.removeAllFeatures();
         myLocation.addFeatures([
            new OpenLayers.Feature.Vector(
                e.point, {}, {
                    graphicName: 'cross',
                    strokeColor: '#f00',
                    strokeWidth: 2,
                    fillOpacity: 0,
                    pointRadius: 10
                })
        ]);
        map.zoomToExtent(myLocation.getDataExtent());
    });

    map.setCenter(new OpenLayers.LonLat(144736, 184000), 12);

    //desktop click handling
    map.events.register("click", map, function(e){updateloc(e)});

    $("#plus").bind('vclick', function () {
        map.zoomIn();
    });
    $("#minus").bind('vclick', function () {
        map.zoomOut();
    });
    $("#locate").bind('vclick', function () {
        var control = map.getControlsBy("id", "locate-control")[0];
        if (control.active) {
            console.log("was already active");
            control.getCurrentLocation();
        } else {
            console.log("activated");
            control.activate();
        }
    });

//
//    var style = {
//        fillOpacity: 0.1,
//        fillColor: '#000',
//        strokeColor: '#f00',
//        strokeOpacity: 0.6
//    };

    $(window).bind("orientationchange resize pageshow", fixContentHeight);


    $("#chooseFile").click(function(e){
        e.preventDefault();
        $("input[type=file]").trigger("click");
    });





    $("input[type=file]").change(function(){
        var file = $("input[type=file]")[0].files[0];
        var reader = new FileReader();

        reader.onerror = function (event) {
            $("#content").innerHTML = "Error bij inladen bestand: " + event;
        };

        reader.onload = function (event) {
            var img = new Image();


            if (event.target.result && event.target.result.match(/^data:base64/)) {
                img.src = event.target.result.replace(/^data:base64/, 'data:image/jpeg;base64');
            } else {
                img.src = event.target.result;
            }


            if (navigator.userAgent.match(/mobile/i) && window.orientation === 0) {
                img.height = 250;
                img.className = 'rotate';
            } else {
                img.width = 400;
            }

            $("#content").innerHTML = '';
            $("#content").append(img);
            return false;
        };

        reader.readAsDataURL(file);

    });

    function updateloc(ev) {
        var lonlat = map.getLonLatFromPixel(ev.xy);

        var point = new OpenLayers.Feature.Vector(
            new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat)
        );

        var fromProjection = new OpenLayers.Projection("EPSG:31370");
        var toProjection   = new OpenLayers.Projection("EPSG:4326");

        var gpslonlat = lonlat.clone();
        gpslonlat.transform(fromProjection, toProjection);

        markerLayer.destroyFeatures();
        markerLayer.addFeatures(point);

        var req_url = "/wegendatabank/v1/locator/xy2loc?x=" + lonlat.lon + "&y=" + lonlat.lat + "&crs=31370";

        $.get(req_url, function(data) {
            var locjson = jQuery.parseJSON(data);
            $("#info").empty();
            $("#info").append("<li><span> "+ locjson.ident8 +"  (refpt. " + locjson.position + ")</span></li>");

            if ($("#info").hasClass('ui-listview')) {
                // If it has the class ui-listview, the listview is initialized and we should call a refresh
                $("#info").append("<li>Lambert72: <span> (" + lonlat.lon + "," + lonlat.lat + ");  GPS: ( " + gpslonlat.lon + "," + gpslonlat.lat + ") </span></li>").listview("refresh");
            } else {

                $("#info").append("<li>Lambert72: <span> (" + lonlat.lon + "," + lonlat.lat + ");  GPS: ( " + gpslonlat.lon + "," + gpslonlat.lat + ") </span></li>");
            }


            $("#wgs84lon").val(gpslonlat.lon);
            $("#wgs84lat").val(gpslonlat.lat);
            $("#ident8").val(locjson.ident8);
            $("#refpt").val(locjson.position);
            $("#lambert72").val("(" + lonlat.lon + "," + lonlat.lat + ")");
            $("#wgs84").val("(" + gpslonlat.lon + "," + gpslonlat.lat + ")");

        });
    }

}


