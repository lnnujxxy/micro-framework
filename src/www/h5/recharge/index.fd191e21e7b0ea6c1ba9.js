(window.webpackJsonp=window.webpackJsonp||[]).push([[0],{0:function(e,t,n){e.exports=n("3Por")},"3Por":function(e,t,n){"use strict";n.r(t);var i=n("NthX"),c=n.n(i),o=n("5WRv"),r=n.n(o),a=(n("GkPX"),n("W1QL"),n("K/PF"),n("3DBk"),n("nxTg")),s=n.n(a),u=(n("wcNg"),n("fFdx")),d=n.n(u),f=(n("nsbO"),n("1UZS"),n("JLrZ"),n("3WoV"),n("ecfn"),n("gki9")),p=n.n(f),l=n("4WqL"),m=n.n(l),v=n("vvX8"),h=n.n(v),w=(n("DbwS"),n("5hJT"),function(e,t){return e.then(function(e){return[null,e]}).catch(function(e){return t&&Object.assign(e,t),[e,void 0]})}),g={filter:function(e,t,n){e&&0===e.errno?t(e.data):n(e)}},x=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},t=p()({},g,e).filter;return function(e){var n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},i=function(){var e=arguments.length>0&&void 0!==arguments[0]&&arguments[0],t={};return t.promise=new Promise(function(e,n){t.resolve=e,t.reject=n}),e&&(t.promise=w(t.promise)),t}();return m()("".concat(e,"?").concat(h.a.stringify(n)),{},function(e,n){e?i.reject(e):t(n,i.resolve,i.reject)}),i.promise}},y=(x(),n("Wz/h"),window.location.href),b=new URLSearchParams(window.location.search.slice(1)).get("code"),S=x({filter:function(e,t,n){e&&0===e.code?t(e.data):n(e)}}),W=function(e){return w(S("https://api.foxibiji.com/User/auth",e))},E=function(e){return w(S("https://api.foxibiji.com/WxPay/config",e))},k=function(e){return w(S("https://api.foxibiji.com/WxPay/unifiedOrderOfficialAccount",e))},L=function(e,t){var n,i,c=[];for(n=0,i=-1;n<e.length;n++)n%t==0&&(c[++i]=[]),c[i].push(e[n]);return c};b?d()(c.a.mark(function e(){var t,n,i,o,a,u,f,p,l,m,v,h,w;return c.a.wrap(function(e){for(;;)switch(e.prev=e.next){case 0:return e.next=2,W({code:b});case 2:if(t=e.sent,n=s()(t,2),i=n[0],o=n[1],i){e.next=15;break}return a=o.openid,e.next=10,E();case 10:u=e.sent,f=s()(u,2),p=f[0],l=f[1],p||(m=Object.values(l),v=L(m,3),h="",v.forEach(function(e){h+='<div class="row">',h+=e.map(function(e){return'\n              <div class="item" data-id="'.concat(e.id,'">\n              <div class="pricc">').concat(e.name,'</div>\n              <div class="commodity">').concat(e.amount,"菩提</div>\n            </div>\n              ")}).join(""),h+="</div>"}),document.body.querySelector("#list").innerHTML=h,w=-1,setTimeout(function(){r()(document.querySelectorAll("#list .item")).forEach(function(e,t){e.addEventListener("click",function(){var n=document.querySelector("#list .selected");n&&n.classList.remove("selected"),w=t,e.classList.add("selected")})})}),document.querySelector(".u-doit button").addEventListener("click",d()(c.a.mark(function e(){var t,n,i,o,r;return c.a.wrap(function(e){for(;;)switch(e.prev=e.next){case 0:if(r=function(){var e=o.pay_sign,t=o.prepay_id,n=o.nonce_str,i=o.timestamp,c=o.appid;WeixinJSBridge.invoke("getBrandWCPayRequest",{appId:c,timeStamp:"".concat(i),nonceStr:n,package:"prepay_id=".concat(t),signType:"MD5",paySign:e},function(e){console.log(e),"get_brand_wcpay_request:ok"==e.err_msg&&alert(支付成功)})},t=m[w]){e.next=4;break}return e.abrupt("return",alert("请选择充值金额"));case 4:return e.next=6,k({id:t.id,openid:a});case 6:n=e.sent,i=s()(n,2),i[0],o=i[1],"undefined"==typeof WeixinJSBridge?document.addEventListener?document.addEventListener("WeixinJSBridgeReady",r,!1):document.attachEvent&&(document.attachEvent("WeixinJSBridgeReady",r),document.attachEvent("onWeixinJSBridgeReady",r)):r();case 11:case"end":return e.stop()}},e,this)}))));case 15:case"end":return e.stop()}},e,this)}))():window.location.href="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".concat("wx95d31b522a383118","&redirect_uri=").concat(encodeURIComponent(y),"&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect")},"3WoV":function(e,t,n){},JLrZ:function(e,t,n){},"Wz/h":function(e,t,n){},ecfn:function(e,t){var n=document,i=window,c=n.documentElement,o=n.querySelector("html").getAttribute("data-designWidth")||375,r=null,a=n.querySelector('meta[name="viewport"]'),s=function(){var e=c.getBoundingClientRect().width;e>540&&(e=540),c.style.fontSize="".concat(e/(o/100),"px")};a||((a=n.createElement("meta")).setAttribute("name","viewport"),a.setAttribute("content","user-scalable=no,initial-scale=1,maximum-scale=1,viewport-fit=cover"),c.firstElementChild.appendChild(a)),i.addEventListener("resize",function(){clearTimeout(r),r=setTimeout(s,300)},!1),i.addEventListener("pageshow",function(e){e.persisted&&(clearTimeout(r),r=setTimeout(s,300))},!1),s()}},[[0,1,2]]]);