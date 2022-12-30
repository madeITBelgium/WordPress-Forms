(()=>{"use strict";var e,l={914:()=>{function e(){return e=Object.assign?Object.assign.bind():function(e){for(var l=1;l<arguments.length;l++){var t=arguments[l];for(var a in t)Object.prototype.hasOwnProperty.call(t,a)&&(e[a]=t[a])}return e},e.apply(this,arguments)}const l=window.wp.element,t=window.wp.blocks,a=window.wp.primitives,n=(0,l.createElement)(a.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},(0,l.createElement)(a.Path,{d:"M4 4v1.5h16V4H4zm8 8.5h8V11h-8v1.5zM4 20h16v-1.5H4V20zm4-8c0-1.1-.9-2-2-2s-2 .9-2 2 .9 2 2 2 2-.9 2-2z"})),r=window.wp.blockEditor,c=window.wp.i18n,i=window.lodash,s=window.wp.components;(0,t.registerBlockType)("madeitforms/multi-value-field",{icon:n,edit:function(t){const{attributes:a,setAttributes:n,className:o,clientId:u}=t,{type:m,required:d,name:p,label:v,default_value:h,placeholder:f,values:b}=a;console.log(t);const E=[{value:"select",label:(0,c.__)("Dropdown")},{value:"radio",label:(0,c.__)("Radio")},{value:"checkbox",label:(0,c.__)("Checkbox")},{value:"multi-select",label:(0,c.__)("Multi Select")}];null==p&&n({name:"field-"+(0,i.uniqueId)()});const g=(0,r.useBlockProps)({className:o}),_={className:"madeit-forms-multi-value-field",type:m,name:p,placeholder:f,disabled:!0};for(var k=wp.data.select("core/block-editor").getBlocks(),w=!0,y=0;y<k.length;y++)k[y].clientId!==u&&void 0!==k[y].attributes.name&&k[y].attributes.name===p&&(w=!1);var x=[],C=b.split(/\r?\n/);if("select"===m||"multi-select"===m)for(null!==f&&""!==f&&x.push((0,l.createElement)("option",null,f)),y=0;y<C.length;y++)x.push((0,l.createElement)("option",{value:C[y],selected:h===C[y]},C[y]));else if("radio"===m)for(y=0;y<C.length;y++)x.push((0,l.createElement)("div",{className:"madeit-forms-radio-field"},(0,l.createElement)("input",{type:m,name:p,value:C[y],checked:h===C[y]}),C[y]));else if("checkbox"===m)for(y=0;y<C.length;y++)x.push((0,l.createElement)("div",{className:"madeit-forms-checkbox-field"},(0,l.createElement)("input",{type:m,name:p+"[]",value:C[y],checked:h===C[y]}),C[y]));return[(0,l.createElement)(r.InspectorControls,null,(0,l.createElement)(s.PanelBody,{title:(0,c.__)("Field settings"),initialOpen:!0},(0,l.createElement)(s.SelectControl,{label:(0,c.__)("Type"),value:m,options:E.map((e=>{let{value:l,label:t}=e;return{value:l,label:t}})),onChange:e=>n({type:e})}),(0,l.createElement)(s.TextControl,{label:(0,c.__)("Label"),value:v,onChange:e=>n({label:e})}),(0,l.createElement)(s.TextControl,{label:(0,c.__)("Default Value"),value:h,onChange:e=>n({default_value:e})}),(0,l.createElement)(s.TextControl,{label:(0,c.__)("Placeholder"),value:f,onChange:e=>n({placeholder:e})}),(0,l.createElement)(s.ToggleControl,{label:(0,c.__)("Required"),checked:d,onChange:e=>n({required:e})}),(0,l.createElement)(s.TextControl,{label:(0,c.__)("Name"),help:(0,c.__)("Deze naam kan je gebruiken in de acties. Enkel letters, cijfers, - of _ zijn toegelaten."),value:p,onChange:e=>{e.toLowerCase().replace(/[^a-z0-9-_]/gi,""),n({name:e})}}),(0,l.createElement)(s.TextareaControl,{label:(0,c.__)("Values"),help:(0,c.__)("Values, each line is a new value."),value:b,onChange:e=>{n({values:e})}}))),(0,l.createElement)("div",null,(0,l.createElement)("div",g,(0,l.createElement)("div",null,(0,l.createElement)("label",null,v)),"select"===m&&(0,l.createElement)("select",_,x),("radio"===m||"checkbox"===m)&&(0,l.createElement)("div",null,x),"multi-select"===m&&(0,l.createElement)("select",e({multiple:!0},_),x)),!w&&(0,l.createElement)("div",{className:"ma-forms-input-error"},(0,c.__)("Duplicated name found. Make the name of this field unique.")))]},save:function(t){const{attributes:a,className:n,clientId:c}=t,{type:i,required:s,name:o,label:u,default_value:m,placeholder:d,values:p}=a,v=r.useBlockProps.save({className:n}),h={className:"madeit-forms-multi-value-field",type:i,name:o,required:s,placeholder:d};var f=[],b=p.split(/\r?\n/);if("select"===i||"multi-select"===i){null!==d&&""!==d&&f.push((0,l.createElement)("option",null,d));for(var E=0;E<b.length;E++)f.push((0,l.createElement)("option",{value:b[E],selected:m===b[E]},b[E]))}else if("radio"===i)for(E=0;E<b.length;E++)f.push((0,l.createElement)("div",{className:"madeit-forms-radio-field"},(0,l.createElement)("label",null,(0,l.createElement)("input",{type:i,name:o,value:b[E],checked:m===b[E]}),(0,l.createElement)("span",null,b[E]))));else if("checkbox"===i)for(E=0;E<b.length;E++)f.push((0,l.createElement)("div",{className:"madeit-forms-checkbox-field"},(0,l.createElement)("label",null,(0,l.createElement)("input",{type:i,name:o+"[]",value:b[E],checked:m===b[E]}),(0,l.createElement)("span",null,b[E]))));return(0,l.createElement)("div",v,(0,l.createElement)("div",null,(0,l.createElement)("label",null,u)),(0,l.createElement)("div",null,"select"===i&&(0,l.createElement)("select",h,f),("radio"===i||"checkbox"===i)&&(0,l.createElement)("div",null,f),"multi-select"===i&&(0,l.createElement)("select",e({multiple:!0},h),f)))},deprecated:[{attributes:{type:{type:"string",default:"select",enum:["select","multi-select","radio","checkbox"]},required:{type:"boolean",default:!1},name:{type:"string"},default_value:{type:"string"},placeholder:{type:"string"},label:{type:"string",default:"Label"},values:{type:"string",default:"Waarde 1\nWaarde 2"}},supports:{html:!1},save(t){const{attributes:a,className:n,clientId:c}=t,{type:i,required:s,name:o,label:u,default_value:m,placeholder:d,values:p}=a,v=r.useBlockProps.save({className:n}),h={className:"madeit-forms-multi-value-field",type:i,name:o,required:s,placeholder:d};var f=[],b=p.split(/\r?\n/);if("select"===i||"multi-select"===i){null!==d&&""!==d&&f.push((0,l.createElement)("option",null,d));for(var E=0;E<b.length;E++)f.push((0,l.createElement)("option",{value:b[E],selected:m===b[E]},b[E]))}else if("radio"===i)for(E=0;E<b.length;E++)f.push((0,l.createElement)("div",{className:"madeit-forms-radio-field"},(0,l.createElement)("input",{type:i,name:o,value:b[E],checked:m===b[E]}),b[E]));else if("checkbox"===i)for(E=0;E<b.length;E++)f.push((0,l.createElement)("div",{className:"madeit-forms-checkbox-field"},(0,l.createElement)("input",{type:i,name:o+"[]",value:b[E],checked:m===b[E]}),b[E]));return(0,l.createElement)("div",v,(0,l.createElement)("div",null,(0,l.createElement)("label",null,u)),(0,l.createElement)("div",null,"select"===i&&(0,l.createElement)("select",h,f),("radio"===i||"checkbox"===i)&&(0,l.createElement)("div",null,f),"multi-select"===i&&(0,l.createElement)("select",e({multiple:!0},h),f)))}}]})}},t={};function a(e){var n=t[e];if(void 0!==n)return n.exports;var r=t[e]={exports:{}};return l[e](r,r.exports,a),r.exports}a.m=l,e=[],a.O=(l,t,n,r)=>{if(!t){var c=1/0;for(u=0;u<e.length;u++){t=e[u][0],n=e[u][1],r=e[u][2];for(var i=!0,s=0;s<t.length;s++)(!1&r||c>=r)&&Object.keys(a.O).every((e=>a.O[e](t[s])))?t.splice(s--,1):(i=!1,r<c&&(c=r));if(i){e.splice(u--,1);var o=n();void 0!==o&&(l=o)}}return l}r=r||0;for(var u=e.length;u>0&&e[u-1][2]>r;u--)e[u]=e[u-1];e[u]=[t,n,r]},a.o=(e,l)=>Object.prototype.hasOwnProperty.call(e,l),(()=>{var e={826:0,431:0};a.O.j=l=>0===e[l];var l=(l,t)=>{var n,r,c=t[0],i=t[1],s=t[2],o=0;if(c.some((l=>0!==e[l]))){for(n in i)a.o(i,n)&&(a.m[n]=i[n]);if(s)var u=s(a)}for(l&&l(t);o<c.length;o++)r=c[o],a.o(e,r)&&e[r]&&e[r][0](),e[r]=0;return a.O(u)},t=self.webpackChunkmadeit_forms=self.webpackChunkmadeit_forms||[];t.forEach(l.bind(null,0)),t.push=l.bind(null,t.push.bind(t))})();var n=a.O(void 0,[431],(()=>a(914)));n=a.O(n)})();