(()=>{"use strict";var e,l={233:()=>{const e=window.wp.element,l=window.wp.blocks,t=window.wp.primitives,a=window.wp.blockEditor,r=window.wp.i18n,n=window.lodash,o=window.wp.components,i={type:{type:"string",default:"text",enum:["text","email","url","tel","password"]},required:{type:"boolean",default:!1},name:{type:"string"},default_value:{type:"string"},placeholder:{type:"string"},label:{type:"string"}},u={html:!1};(0,l.registerBlockType)("madeitforms/input-field",{icon:(0,e.createElement)(t.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},(0,e.createElement)(t.Path,{d:"M20 6H4c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm.5 11c0 .3-.2.5-.5.5H4c-.3 0-.5-.2-.5-.5V8c0-.3.2-.5.5-.5h16c.3 0 .5.2.5.5v9zM10"})),supports:u,attributes:i,edit:function(l){const{attributes:t,setAttributes:i,className:u,clientId:d}=l,{type:c,required:s,name:m,label:p,default_value:f,placeholder:v}=t;console.log(l);const b=[{value:"text",label:(0,r.__)("Text")},{value:"email",label:(0,r.__)("E-mail Address")},{value:"url",label:(0,r.__)("URL")},{value:"tel",label:(0,r.__)("Phone")}];null==m&&i({name:"field-"+(0,n.uniqueId)()});const h=(0,a.useBlockProps)({className:u}),_={className:"madeit-forms-input-field",type:c,name:m,value:f,placeholder:v,disabled:!0};for(var g=wp.data.select("core/block-editor").getBlocks(),k=!0,w=0;w<g.length;w++)g[w].clientId!==d&&void 0!==g[w].attributes.name&&g[w].attributes.name===m&&(k=!1);return[(0,e.createElement)(a.InspectorControls,null,(0,e.createElement)(o.PanelBody,{title:(0,r.__)("Field settings"),initialOpen:!0},(0,e.createElement)(o.SelectControl,{label:(0,r.__)("Type"),value:c,options:b.map((e=>{let{value:l,label:t}=e;return{value:l,label:t}})),onChange:e=>i({type:e})}),(0,e.createElement)(o.TextControl,{label:(0,r.__)("Label"),value:p,onChange:e=>i({label:e})}),(0,e.createElement)(o.TextControl,{label:(0,r.__)("Default Value"),value:f,onChange:e=>i({default_value:e})}),(0,e.createElement)(o.TextControl,{label:(0,r.__)("Placeholder"),value:v,onChange:e=>i({placeholder:e})}),(0,e.createElement)(o.ToggleControl,{label:(0,r.__)("Required"),checked:s,onChange:e=>i({required:e})}),(0,e.createElement)(o.TextControl,{label:(0,r.__)("Name"),help:(0,r.__)("Deze naam kan je gebruiken in de acties. Enkel letters, cijfers, - of _ zijn toegelaten."),value:m,onChange:e=>{e.toLowerCase().replace(/[^a-z0-9-_]/gi,""),i({name:e})}}))),(0,e.createElement)("div",null,(0,e.createElement)("div",h,(0,e.createElement)("div",null,(0,e.createElement)("label",null,p)),(0,e.createElement)("input",_)),!k&&(0,e.createElement)("div",{className:"ma-forms-input-error"},(0,r.__)("Duplicated name found. Make the name of this field unique.")))]},save:function(l){const{attributes:t,className:r,clientId:n}=l,{type:o,required:i,name:u,label:d,default_value:c,placeholder:s}=t,m=a.useBlockProps.save({className:r}),p={className:"madeit-forms-input-field",type:o,name:u,required:i,value:c,placeholder:s};return(0,e.createElement)("div",m,null!==d&&d.length>0?(0,e.createElement)("div",null,(0,e.createElement)("label",null,d)):null,(0,e.createElement)("input",p))},deprecated:[{attributes:i,supports:u,save:function(l){const{attributes:t,className:r}=l,{type:n,required:o,name:i,label:u,default_value:d,placeholder:c}=t,s=a.useBlockProps.save({className:r}),m={className:"madeit-forms-input-field",type:n,name:i,required:o,value:d,placeholder:c};return(0,e.createElement)("div",s,(0,e.createElement)("div",null,(0,e.createElement)("label",null,u)),(0,e.createElement)("input",m))}}],transforms:{from:[{type:"block",blocks:["madeitforms/largeinput-field"],transform:e=>(0,l.createBlock)("madeitforms/input-field",{type:"text",required:e.required,name:e.name,default_value:e.default_value,placeholder:e.placeholder,label:e.label})},{type:"block",blocks:["madeitforms/multi-value-field"],transform:e=>(0,l.createBlock)("madeitforms/input-field",{type:"text",required:e.required,name:e.name,default_value:e.default_value,placeholder:e.placeholder,label:e.label})}],to:[{type:"block",blocks:["madeitforms/largeinput-field"],transform:e=>(0,l.createBlock)("madeitforms/largeinput-field",{required:e.required,name:e.name,default_value:e.default_value,placeholder:e.placeholder,label:e.label})},{type:"block",blocks:["madeitforms/multi-value-field"],transform:e=>(0,l.createBlock)("madeitforms/multi-value-field",{type:"checkbox",required:e.required,name:e.name,default_value:e.default_value,placeholder:e.placeholder,label:e.label,values:e.default_value})}]}})}},t={};function a(e){var r=t[e];if(void 0!==r)return r.exports;var n=t[e]={exports:{}};return l[e](n,n.exports,a),n.exports}a.m=l,e=[],a.O=(l,t,r,n)=>{if(!t){var o=1/0;for(c=0;c<e.length;c++){t=e[c][0],r=e[c][1],n=e[c][2];for(var i=!0,u=0;u<t.length;u++)(!1&n||o>=n)&&Object.keys(a.O).every((e=>a.O[e](t[u])))?t.splice(u--,1):(i=!1,n<o&&(o=n));if(i){e.splice(c--,1);var d=r();void 0!==d&&(l=d)}}return l}n=n||0;for(var c=e.length;c>0&&e[c-1][2]>n;c--)e[c]=e[c-1];e[c]=[t,r,n]},a.o=(e,l)=>Object.prototype.hasOwnProperty.call(e,l),(()=>{var e={826:0,431:0};a.O.j=l=>0===e[l];var l=(l,t)=>{var r,n,o=t[0],i=t[1],u=t[2],d=0;if(o.some((l=>0!==e[l]))){for(r in i)a.o(i,r)&&(a.m[r]=i[r]);if(u)var c=u(a)}for(l&&l(t);d<o.length;d++)n=o[d],a.o(e,n)&&e[n]&&e[n][0](),e[n]=0;return a.O(c)},t=self.webpackChunkmadeit_forms=self.webpackChunkmadeit_forms||[];t.forEach(l.bind(null,0)),t.push=l.bind(null,t.push.bind(t))})();var r=a.O(void 0,[431],(()=>a(233)));r=a.O(r)})();