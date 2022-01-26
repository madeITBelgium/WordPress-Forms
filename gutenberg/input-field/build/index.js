!function(){"use strict";var e,l={233:function(){var e=window.wp.element,l=window.wp.blocks,t=window.wp.primitives,n=window.wp.i18n,a=window.lodash,r=window.wp.blockEditor,o=window.wp.components;(0,l.registerBlockType)("madeitforms/input-field",{icon:(0,e.createElement)(t.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},(0,e.createElement)(t.Path,{d:"M20 6H4c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm.5 11c0 .3-.2.5-.5.5H4c-.3 0-.5-.2-.5-.5V8c0-.3.2-.5.5-.5h16c.3 0 .5.2.5.5v9zM10"})),edit:function(l){const{attributes:t,setAttributes:i,className:c,clientId:u}=l,{type:s,required:d,name:m,label:p,default_value:v,placeholder:f}=t;console.log(l);const b=[{value:"text",label:(0,n.__)("Text")},{value:"email",label:(0,n.__)("E-mail Address")},{value:"url",label:(0,n.__)("URL")},{value:"tel",label:(0,n.__)("Phone")}];null==m&&i({name:"field-"+(0,a.uniqueId)()});const h=(0,r.useBlockProps)({className:c}),_={className:"madeit-forms-input-field",type:s,name:m,value:v,placeholder:f,disabled:!0};for(var w=wp.data.select("core/block-editor").getBlocks(),E=!0,g=0;g<w.length;g++)w[g].clientId!==u&&void 0!==w[g].attributes.name&&w[g].attributes.name===m&&(E=!1);return[(0,e.createElement)(r.InspectorControls,null,(0,e.createElement)(o.PanelBody,{title:(0,n.__)("Field settings"),initialOpen:!0},(0,e.createElement)(o.SelectControl,{label:(0,n.__)("Type"),value:s,options:b.map((e=>{let{value:l,label:t}=e;return{value:l,label:t}})),onChange:e=>i({type:e})}),(0,e.createElement)(o.TextControl,{label:(0,n.__)("Label"),value:p,onChange:e=>i({label:e})}),(0,e.createElement)(o.TextControl,{label:(0,n.__)("Default Value"),value:v,onChange:e=>i({default_value:e})}),(0,e.createElement)(o.TextControl,{label:(0,n.__)("Placeholder"),value:f,onChange:e=>i({placeholder:e})}),(0,e.createElement)(o.ToggleControl,{label:(0,n.__)("Required"),checked:d,onChange:e=>i({required:e})}),(0,e.createElement)(o.TextControl,{label:(0,n.__)("Name"),help:(0,n.__)("Deze naam kan je gebruiken in de acties. Enkel letters, cijfers, - of _ zijn toegelaten."),value:m,onChange:e=>{e.toLowerCase().replace(/[^a-z0-9-_]/gi,""),i({name:e})}}))),(0,e.createElement)("div",null,(0,e.createElement)("div",h,(0,e.createElement)("div",null,(0,e.createElement)("label",null,p)),(0,e.createElement)("input",_)),!E&&(0,e.createElement)("div",{className:"ma-forms-input-error"},(0,n.__)("Duplicated name found. Make the name of this field unique.")))]},save:function(l){const{attributes:t,className:n,clientId:a}=l,{type:o,required:i,name:c,label:u,default_value:s,placeholder:d}=t,m=r.useBlockProps.save({className:n}),p={className:"madeit-forms-input-field",type:o,name:c,required:i,value:s,placeholder:d};return(0,e.createElement)("div",m,(0,e.createElement)("div",null,(0,e.createElement)("label",null,u)),(0,e.createElement)("input",p))}})}},t={};function n(e){var a=t[e];if(void 0!==a)return a.exports;var r=t[e]={exports:{}};return l[e](r,r.exports,n),r.exports}n.m=l,e=[],n.O=function(l,t,a,r){if(!t){var o=1/0;for(s=0;s<e.length;s++){t=e[s][0],a=e[s][1],r=e[s][2];for(var i=!0,c=0;c<t.length;c++)(!1&r||o>=r)&&Object.keys(n.O).every((function(e){return n.O[e](t[c])}))?t.splice(c--,1):(i=!1,r<o&&(o=r));if(i){e.splice(s--,1);var u=a();void 0!==u&&(l=u)}}return l}r=r||0;for(var s=e.length;s>0&&e[s-1][2]>r;s--)e[s]=e[s-1];e[s]=[t,a,r]},n.o=function(e,l){return Object.prototype.hasOwnProperty.call(e,l)},function(){var e={826:0,46:0};n.O.j=function(l){return 0===e[l]};var l=function(l,t){var a,r,o=t[0],i=t[1],c=t[2],u=0;if(o.some((function(l){return 0!==e[l]}))){for(a in i)n.o(i,a)&&(n.m[a]=i[a]);if(c)var s=c(n)}for(l&&l(t);u<o.length;u++)r=o[u],n.o(e,r)&&e[r]&&e[r][0](),e[o[u]]=0;return n.O(s)},t=self.webpackChunkmadeit_forms=self.webpackChunkmadeit_forms||[];t.forEach(l.bind(null,0)),t.push=l.bind(null,t.push.bind(t))}();var a=n.O(void 0,[46],(function(){return n(233)}));a=n.O(a)}();