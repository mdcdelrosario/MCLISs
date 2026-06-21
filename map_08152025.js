let map;
let markers = [];
let markere = [];
let markerc = [];

function clearCars(){
    for (let i = 0; i < markerc.length; i++) {
        markerc[i].setMap(null);
    }
    markerc = [];
}

function clearElements(){
    for (let i = 0; i < markere.length; i++) {
        markere[i].setMap(null);
    }
    markere = [];
}

function viewCars(features = []) {
    clearCars(); // Clear old markers

    const iconBase = "iconsx/";
    const bounds = new google.maps.LatLngBounds();

    for (let i = 0; i < features.length; i++) {
        const lat = parseFloat(features[i].lat);
        const lng = parseFloat(features[i].lng);
        const crew = features[i].crew || "N/A";

        if (!isNaN(lat) && !isNaN(lng)) {
            const latLng = new google.maps.LatLng(lat, lng);

            const image = {
                url: iconBase + "car.png",
                scaledSize: new google.maps.Size(75, 75),
                origin: new google.maps.Point(0, 0),
                labelOrigin: new google.maps.Point(38, 13)
            };

            const marker = new google.maps.Marker({
                position: latLng,
                icon: image,
                label: {
                    text: crew,
                    color: "blue",
                    fontSize: "14px",
                    fontWeight: "bold"
                },
                draggable: false,
                map: map
            });

            bounds.extend(latLng);
            markerc.push(marker);
        } else {
            console.warn("Skipped invalid point:", features[i]);
        }
    }

    // Only center map if there are valid markers
    // if (markerc.length > 0) {
    //     map.setCenter(bounds.getCenter());
    //     map.fitBounds(bounds);
    //     map.setZoom(map.getZoom() - 1);
    // }
}


function viewElements(features=[]) {

    for (let i = 0; i < markere.length; i++) {
        markere[i].setMap(null);
    }
    markere = [];

    //const iconBase = "http://maps.google.com/mapfiles/ms/iconsx/";
    const iconBase = "iconsx/";


    var bounds = new google.maps.LatLngBounds();

    // Create markers.
    for (let i = 0; i < features.length; i++) {
        var loc_icon = iconBase + features[i].icon

        const image = {
            url: loc_icon,
            scaledSize: new google.maps.Size(25,25),
            origin: new google.maps.Point(0, 0),
          };


        const marker = new google.maps.Marker({
            position: features[i].position,
            icon: image,
            draggable:false,
            map: map,
        })

        bounds.extend(features[i].position);

        var infowindow =  new google.maps.InfoWindow({
            map: map,
            position: features[i].position
        })

        marker.addListener('mouseover', function() {
            infowindow.setContent(
                '<b>ID : ' + features[i].id + '</b>'+
                '<br>TYPE : ' + features[i].type +  
                '<br>FEEDER : ' + features[i].feeder 
                );
            infowindow.open(map, this);
        });
        
        marker.addListener('mouseout', function() {
            infowindow.close();
        });


        infowindow.close(); 

        markere.push(marker);
    }


    //center the map to the geometric center of all markers
    map.setCenter(bounds.getCenter());

    map.fitBounds(bounds);

    //remove one zoom level to ensure no marker is on the edge.
    map.setZoom(map.getZoom()-1); 


}

function searchMapCNR(features=[]) {
    //map = new google.maps.Map(document.getElementById("map"), {
    //  center: new google.maps.LatLng(15.904513333333334, 120.38383333333334),
    //  zoom: 10,
    //});

    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers = [];

    //const iconBase = "http://maps.google.com/mapfiles/ms/iconsx/";
    const iconBase = "iconsx/";


    var bounds = new google.maps.LatLngBounds();

    // Create markers.
    for (let i = 0; i < features.length; i++) {
        var loc_icon = iconBase + "cnr.png"

        const image = {
            url: loc_icon,
            scaledSize: new google.maps.Size(35,35),
            origin: new google.maps.Point(0, 0),
          };


        const marker = new google.maps.Marker({
            position: features[i].position,
            icon: image,
            draggable:false,
            map: map,
        })

        bounds.extend(features[i].position);

        var infowindow =  new google.maps.InfoWindow({
            map: map,
            position: features[i].position
        })

        marker.addListener('mouseover', function() {
            infowindow.setContent(
                '<b>' + features[i].name + '</b>'+
                '<br>ADDRESS : ' + features[i].address +  
                '<br>CONTACT NO : ' + features[i].contactno +  
                '<br>COMPLAINT : ' +features[i].cnr +
                '<br>CREW : ' +features[i].crew 
                );
            infowindow.open(map, this);
        });
        
        marker.addListener('mouseout', function() {
            infowindow.close();
        });

        marker.addListener("click", () => {
            //PageLink('custinfo.php','dvcustinfo','fsearch','&vaccount='+vaccountno);
            PageLink('comnreq.php','dvcustinfo3','fsearch','&trn='+features[i].trn);
            document.getElementById('id03').style.display='block';
          });

        infowindow.close(); 

        markers.push(marker);
    }


    // //center the map to the geometric center of all markers
    // map.setCenter(bounds.getCenter());

    // map.fitBounds(bounds);

    // //remove one zoom level to ensure no marker is on the edge.
    // map.setZoom(map.getZoom()-1); 


}

function searchMap(features=[]) {
    //map = new google.maps.Map(document.getElementById("map"), {
    //  center: new google.maps.LatLng(15.904513333333334, 120.38383333333334),
    //  zoom: 10,
    //});

    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers = [];

    //const iconBase = "http://maps.google.com/mapfiles/ms/iconsx/";
    const iconBase = "iconsx/";

    var bounds = new google.maps.LatLngBounds();

    // Create markers.
    for (let i = 0; i < features.length; i++) {
        var loc_icon = iconBase + "meter.png"
        if(features[i].type == "A"){
        //    var loc_icon = iconBase + "green-dot.png"
              var loc_icon = iconBase + "meterg.png"
        }
        if(features[i].type == "D"){
        //    var loc_icon = iconBase + "red-dot.png"
            var loc_icon = iconBase + "meterr.png"
        }
        if(features[i].type == "T"){
           // var loc_icon = iconBase + "yellow-dot.png"
            var loc_icon = iconBase + "metery.png"
        }

        const image = {
            url: loc_icon,
            scaledSize: new google.maps.Size(25,25),
            origin: new google.maps.Point(0, 0),
          };

        const marker = new google.maps.Marker({
            position: features[i].position,
            icon: image,
            draggable:false,
            map: map,
        })

        bounds.extend(features[i].position);

        var infowindow =  new google.maps.InfoWindow({
            map: map,
            position: features[i].position
        })

        marker.addListener('mouseover', function() {
            var sffdesc = "";
            if(features[i].ffdesc.trim() != ""){
                sffdesc = '<br>FINDINGS : <font color=\'red\'>' +features[i].ffdesc + '</font>';
            }

            infowindow.setContent(
                '<b>' + features[i].name + '</b>'+
                '<br>ACCOUNT NO : ' + features[i].faccountno +  
                '<br>METER NO : ' +features[i].meterno +
                sffdesc );
            infowindow.open(map, this);
        });
        
        marker.addListener('mouseout', function() {
            infowindow.close();
        });

        //marker.addListener('dragend', function(){
        //   alert('ahole' + this.position.lat() + " > " + this.position.lng());
        //});

        marker.addListener("click", () => {
            //map.setZoom(20);
            //map.setCenter(marker.getPosition());
            PageLink('custinfo.php','dvcustinfo','fsearch','&vaccount='+features[i].accountno);
            document.getElementById('id01').style.display='block';
          });

        infowindow.close(); 

        markers.push(marker);

        // Add Tag Marker
        
        if(features[i].btag != ""){
           
            // var loc_icon = iconBase + "yellow-dot.png"
             var loc_icon = iconBase + "btag.png"
             
             const image = {
                url: loc_icon,
                scaledSize: new google.maps.Size(25,25),
                origin: new google.maps.Point(0, 0),
              };
    
            const marker = new google.maps.Marker({
                position: features[i].position,
                icon: image,
                draggable:false,
                map: map,
            })



            var infowindow =  new google.maps.InfoWindow({
                map: map,
                position: features[i].position
            })
    
            marker.addListener('mouseover', function() {
                var sffdesc = "";
                if(features[i].ffdesc.trim() != ""){
                    sffdesc = '<br>FINDINGS : <font color=\'red\'>' +features[i].ffdesc + '</font>';
                }
    
                infowindow.setContent(
                    '<b>' + features[i].name + '</b>'+
                    '<br>ACCOUNT NO : ' + features[i].faccountno +  
                    '<br>METER NO : ' +features[i].meterno +
                    sffdesc );
                infowindow.open(map, this);
            });
            
            marker.addListener('mouseout', function() {
                infowindow.close();
            });
    
    
            marker.addListener("click", () => {
                PageLink('custinfo.php','dvcustinfo','fsearch','&vaccount='+features[i].accountno);
                document.getElementById('id01').style.display='block';
              });
    
            infowindow.close(); 
    

             markers.push(marker);
         }

    }


    //center the map to the geometric center of all markers
    map.setCenter(bounds.getCenter());

    map.fitBounds(bounds);

    //remove one zoom level to ensure no marker is on the edge.
    map.setZoom(map.getZoom()-1); 


}

function setmapViewCNR(lat,lng,vtrn,vaccountno){
    map.setZoom(20);
    var latLng = new google.maps.LatLng(lat, lng+0.0002);
    map.panTo(latLng);
    //PageLink('custinfo.php','dvcustinfo','fsearch','&vaccount='+vaccountno);
    PageLink('comnreq.php','dvcustinfo3','fsearch','&trn='+vtrn);
    document.getElementById('id03').style.display='block';
    //document.getElementById('id02').style.display='block';
}

function setmapView(lat,lng,vaccountno){
    map.setZoom(20);
    var latLng = new google.maps.LatLng(lat, lng+0.0002);
    map.panTo(latLng);
    PageLink('custinfo.php','dvcustinfo','fsearch','&vaccount='+vaccountno);
    document.getElementById('id01').style.display='block';
}

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
      center: new google.maps.LatLng(15.904513333333334, 120.38383333333334),
      zoom: 10,
      mapTypeControl: true, // Enables map/satellite buttons
      mapTypeControlOptions: {
          position: google.maps.ControlPosition.TOP_LEFT, // Ensures it's at the right
      },
    });

    // Wait for controls to render, then adjust positions individually
    setTimeout(() => {
        const mapButton = document.querySelector('.gm-style-mtc'); // Map/Satellite button
        const satelliteButton = document.querySelectorAll('.gmnoprint div')[1]; // Satellite button
        const pegman = document.querySelector('.gm-svpc'); // Pegman (Street View)
        const zoomControls = document.querySelector('.gmnoprint.gm-bundled-control'); // Zoom controls

        // if (mapButton) {
        //     mapButton.style.position = "absolute";
        //     mapButton.style.top = "10px";
        //     mapButton.style.left = "520px";
        // }

        // if (satelliteButton) {
        //     satelliteButton.style.position = "absolute";
        //     satelliteButton.style.top = "10px";
        //     satelliteButton.style.left = "620px"; // Second button (spaced from first)
        // }

        // if (zoomControls) {
        //     zoomControls.style.position = "absolute";
        //     zoomControls.style.top = "55px";
        //     zoomControls.style.left = "670px"; // Third button (spaced from second)
        // }

        // if (pegman) {
        //     pegman.style.position = "absolute";
        //     pegman.style.top = "-45px";
        //     pegman.style.left = "0px"; // Fourth button (spaced from third)
        // }

    }, 1000); // Wait for controls to load

    map.addListener("click", (mapsMouseEvent) => {

        if(addtag == 'CY'){
            document.getElementById("t_latc").value = mapsMouseEvent.latLng.lat();
            document.getElementById("t_lngc").value = mapsMouseEvent.latLng.lng();   
    
            map.setOptions({draggableCursor:''});
            addtag = 'N';
        }
        if(addtag == 'CNRY'){
            document.getElementById("t_latCNR").value = mapsMouseEvent.latLng.lat();
            document.getElementById("t_lngCNR").value = mapsMouseEvent.latLng.lng();   
    
            map.setOptions({draggableCursor:''});
            addtag = 'N';
        }
     });
}

function ChangeLoc(){
    map.setOptions({draggableCursor:'crosshair'});
    addtag='CY';
}

function ChangeLocCNR(){
    map.setOptions({draggableCursor:'crosshair'});
    addtag='CNRY';
}