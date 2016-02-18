//显示地图
var fafaMap = {
    _map: null,
    _point: null,
    Load: function () {
        var scripts = document.getElementsByTagName("SCRIPT"), isRef = false;
        if (scripts != null) {
            for (var i = 0; i < scripts.length; i++) {
                if (scripts[i].src.split("?")[0].indexOf("api.map.baidu.com") > -1) {
                    isRef = true;
                    break;
                }
            }
        }
        if (!isRef) {
            var oHead = document.getElementsByTagName('HEAD').item(0);
            var oScript = document.createElement("script");
            oScript.type = "text/javascript";
            oScript.src = "http://api.map.baidu.com/getscript?v=1.4&key=&services=&t=20121203042542";
            oHead.appendChild(oScript);
            var head = document.getElementsByTagName('head').item(0);
            css = document.createElement('link');
            css.href = "http://api.map.baidu.com/res/14/bmap.css";
            css.rel = 'stylesheet';
            css.type = 'text/css';
            head.appendChild(css);
        }
    },
    AutoShow: function () {
        var fafa_maps = $(".fafa-map[id!='togetherMap_map']");
        if (fafa_maps == null || fafa_maps.length == 0) return;
        if (typeof (BMap) == "undefined") {
            fafaMap.Load();
            setTimeout("fafaMap.AutoShow()", 100);
            return;
        }
        var fafa_maps = $(".fafa-map[id!='togetherMap_map']");
        for (var i = 0; i < fafa_maps.length; i++) {
            var _tmp = $(fafa_maps[i]);
            if (_tmp.attr("id").indexOf("together_id_") == -1 || _tmp.attr("isload") == "1") continue;
            var P = _tmp.attr("point");
            if (P != null && P != "") {
                P = P.split(",");
                P = { x: P[0] * 1, y: P[1] * 1 };
                var t = new BMap.Map(_tmp.attr("id"));
                t.centerAndZoom(new BMap.Point(P.x, P.y), 17);
                _tmp.css("display", "block");
                var marker1 = new BMap.Marker(new BMap.Point(P.x, P.y));  // 创建标注
                t.addOverlay(marker1);
                t.addControl(new BMap.NavigationControl({ anchor: BMAP_ANCHOR_TOP_LEFT, type: BMAP_NAVIGATION_CONTROL_ZOOM }));                
            }
            else _tmp.css("display", "none");
            _tmp.attr("isload", "1");
        }
    },
    Show: function (owner, P, readOnly) {
        if (typeof (BMap) == "undefined") {
            fafaMap.Load();
            setTimeout("fafaMap.Show('" + owner + "')", 100);
            return;
        }
        if (readOnly != null && !readOnly) $("#" + owner).parent().modal("show");
        fafaMap._map = new BMap.Map(owner);          // 创建地图实例  
        if (readOnly == null || !readOnly) {
            fafaMap._map.addEventListener("click", function (e) {
                fafaMap._map._point = new BMap.Point(e.point.lng, e.point.lat);
                fafaMap._map.setCenter(fafaMap._map._point);
                fafaMap._map.clearOverlays();
                var marker1 = new BMap.Marker(fafaMap._map._point);  // 创建标注
                fafaMap._map.addOverlay(marker1);
            });
        }
        var point = (P == null || P == "") ? new BMap.Point(116.331398, 39.897445) : new BMap.Point(P.x, P.y);
        fafaMap._map.centerAndZoom(point, 17);
        if (P == null || P == "") {
            var localCity = new BMap.LocalCity();
            localCity.get(function (r) {
                fafaMap._map.setCenter(r.name);
            });
        }
        else {
            //如果指定了坐标点，则初始化标注
            fafaMap._map.clearOverlays();
            fafaMap._map._point = new BMap.Point(P.x, P.y);
            fafaMap._map.setCenter(fafaMap._map._point);
            var marker1 = new BMap.Marker(fafaMap._map._point);  // 创建标注
            fafaMap._map.addOverlay(marker1);
        }
        fafaMap._map.addControl(new BMap.NavigationControl({ anchor: BMAP_ANCHOR_TOP_LEFT, type: BMAP_NAVIGATION_CONTROL_LARGE }));
    },
    save: function (func) {
        if (fafaMap._map._point == null) fafaMap._map._point = fafaMap._map.getCenter();
        var gc = new BMap.Geocoder();
        gc.getLocation(fafaMap._map._point, function (rs) {
            var addComp = rs.addressComponents;
            var addr = (addComp.province + "" + addComp.city + " " + addComp.district + "" + addComp.street + "" + addComp.streetNumber);
            func({ "x": fafaMap._map._point.lng, "y": fafaMap._map._point.lat, "addr": addr });
        });
    }
};