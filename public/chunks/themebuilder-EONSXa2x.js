const P=["bootstrap","material","tailwind"],q=["light","dark"],x=["primary","primaryHover","onPrimary","bodyBg","bodyColor","muted","surface","border","inputBorder","danger"],I={none:"none",subtle:"0 1px 2px rgba(0, 0, 0, 0.08)",elevated:"0 1px 2px rgba(0, 0, 0, 0.3), 0 1px 3px 1px rgba(0, 0, 0, 0.15)",outlined:"0 1px 2px 0 rgba(0, 0, 0, 0.05), 0 0 0 1px var(--bs-border-color)"},ae={bootstrap:"0.25",material:"0.35",tailwind:"0.5"},se=`.btn-primary {
  --bs-btn-color: var(--celerik-on-primary);
  --bs-btn-bg: var(--celerik-primary);
  --bs-btn-border-color: var(--celerik-primary);
  --bs-btn-hover-color: var(--celerik-on-primary);
  --bs-btn-hover-bg: var(--celerik-primary-hover);
  --bs-btn-hover-border-color: var(--celerik-primary-hover);
  --bs-btn-active-color: var(--celerik-on-primary);
  --bs-btn-active-bg: var(--celerik-primary-hover);
  --bs-btn-active-border-color: var(--celerik-primary-hover);
  --bs-btn-disabled-color: var(--celerik-on-primary);
  --bs-btn-disabled-bg: var(--celerik-primary);
  --bs-btn-disabled-border-color: var(--celerik-primary);
  --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
}

.btn-outline-primary {
  --bs-btn-color: var(--celerik-primary);
  --bs-btn-border-color: var(--celerik-primary);
  --bs-btn-hover-color: var(--celerik-on-primary);
  --bs-btn-hover-bg: var(--celerik-primary);
  --bs-btn-hover-border-color: var(--celerik-primary);
  --bs-btn-active-color: var(--celerik-on-primary);
  --bs-btn-active-bg: var(--celerik-primary-hover);
  --bs-btn-active-border-color: var(--celerik-primary-hover);
  --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
}

.form-control,
.form-select,
.form-check-input {
  border-color: var(--celerik-input-border);
}

.form-control:focus,
.form-select:focus,
.form-check-input:focus {
  border-color: var(--celerik-primary);
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.form-check-input:checked {
  background-color: var(--celerik-primary);
  border-color: var(--celerik-primary);
}

.nav-pills {
  --bs-nav-pills-link-active-bg: var(--celerik-primary);
  --bs-nav-pills-link-active-color: var(--celerik-on-primary);
}

.pagination {
  --bs-pagination-active-bg: var(--celerik-primary);
  --bs-pagination-active-border-color: var(--celerik-primary);
  --bs-pagination-active-color: var(--celerik-on-primary);
  --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.celerik-scheduler {
  --fc-button-text-color: var(--celerik-on-primary);
  --fc-button-bg-color: var(--celerik-primary);
  --fc-button-border-color: var(--celerik-primary);
  --fc-button-hover-bg-color: var(--celerik-primary-hover);
  --fc-button-hover-border-color: var(--celerik-primary-hover);
  --fc-button-active-bg-color: var(--celerik-primary-hover);
  --fc-button-active-border-color: var(--celerik-primary-hover);
  --fc-event-bg-color: var(--celerik-primary);
  --fc-event-border-color: var(--celerik-primary);
  --fc-event-text-color: var(--celerik-on-primary);
}`;function w(e){return[1,3,5].map(i=>parseInt(e.slice(i,i+2),16)).join(", ")}function F(e,i){return`${e} {
${Object.entries(i).map(([r,t])=>`  ${r}: ${t};
`).join("")}}
`}function D(e,i){const r=e[i],t={"--bs-primary":r.primary,"--bs-primary-rgb":w(r.primary),"--bs-link-color":r.primary,"--bs-link-color-rgb":w(r.primary),"--bs-link-hover-color":r.primaryHover,"--bs-link-hover-color-rgb":w(r.primaryHover),"--bs-primary-text-emphasis":r.primaryHover,"--bs-body-bg":r.bodyBg,"--bs-body-bg-rgb":w(r.bodyBg),"--bs-body-color":r.bodyColor,"--bs-body-color-rgb":w(r.bodyColor),"--bs-secondary-color":r.muted,"--bs-tertiary-bg":r.surface,"--bs-tertiary-bg-rgb":w(r.surface),"--bs-border-color":r.border,"--bs-focus-ring-color":`rgba(${w(r.primary)}, ${ae[e.base]})`,"--bs-form-invalid-color":r.danger,"--bs-form-invalid-border-color":r.danger,"--bs-danger-text-emphasis":r.danger,"--celerik-primary":r.primary,"--celerik-on-primary":r.onPrimary,"--celerik-primary-hover":r.primaryHover,"--celerik-input-border":r.inputBorder};return e.base==="tailwind"&&(t["--celerik-grid-header-color"]=r.muted,t["--celerik-focus-ring"]=`0 0 0 2px ${r.bodyBg}, 0 0 0 4px ${r.primary}`),t}function z(e){const i=e.base==="bootstrap"?"celerik.css":`celerik.css and celerik-${e.base}.css`;let r=`/* Celerik UI theme "${e.name}" (base: ${e.base}). Load it after ${i}. */
`;r+=F(`:root,
[data-bs-theme=light]`,D(e,"light")),r+=`
${F("[data-bs-theme=dark]",D(e,"dark"))}`;const t={};if(e.radius!==void 0&&e.radius!==null){const a=`${Math.round(e.radius*1e3)/1e3}rem`;t["--bs-border-radius"]=a,t["--celerik-radius"]=a}return e.fontFamily&&(t["--bs-body-font-family"]=e.fontFamily),e.shadow&&(t["--celerik-surface-shadow"]=I[e.shadow]),Object.keys(t).length>0&&(r+=`
${F(":root",t)}`),e.base==="bootstrap"&&(r+=`
${se}
`),r}function K(e){const[i,r,t]=[1,3,5].map(a=>parseInt(e.slice(a,a+2),16)/255).map(a=>a<=.03928?a/12.92:((a+.055)/1.055)**2.4);return .2126*i+.7152*r+.0722*t}function ce(e,i){const[r,t]=[K(e),K(i)].sort((a,s)=>s-a);return(r+.05)/(t+.05)}function le(e,i){return q.flatMap(r=>i.map(t=>{const a=ce(e[r][t.foreground],e[r][t.background]);return{...t,mode:r,ratio:a,passes:a>=t.minimum}}))}function de(e){return(Math.floor(e*100)/100).toFixed(2)}const be=/^#[0-9a-f]{6}$/i,ue=/^[\p{L}\p{N} ._-]{1,60}$/u,me=/^[A-Za-z0-9 ,'"-]{1,200}$/,fe=["name","base","light","dark","radius","fontFamily","shadow"];function B(e){return typeof e=="string"&&be.test(e)}function U(e){if(e===null||typeof e!="object"||Array.isArray(e))return{error:"A theme must be a JSON object."};const i=Object.keys(e).filter(t=>!fe.includes(t));if(i.length)return{error:`Unknown theme keys: ${i.join(", ")}.`};if(typeof e.name!="string"||!ue.test(e.name))return{error:"A theme name has 1 to 60 letters, digits, spaces, dots, dashes or underscores."};if(!P.includes(e.base))return{error:`Unknown base "${e.base}". Available: ${P.join(", ")}.`};const r={name:e.name,base:e.base};for(const t of q){const a=e[t];if(a===null||typeof a!="object"||Array.isArray(a))return{error:`The ${t} palette must be an object.`};const s=Object.keys(a);if(s.length!==x.length||!x.every(n=>s.includes(n)))return{error:`The ${t} palette needs exactly these colors: ${x.join(", ")}.`};for(const n of x)if(!B(a[n]))return{error:`Invalid color "${a[n]}" for ${t}.${n}: use #rrggbb.`};r[t]=Object.fromEntries(x.map(n=>[n,a[n].toLowerCase()]))}if(e.radius!==void 0&&e.radius!==null){if(typeof e.radius!="number"||e.radius<0||e.radius>2)return{error:"The radius must be a number between 0 and 2 (rem)."};r.radius=Math.round(e.radius*1e3)/1e3}if(e.fontFamily!==void 0&&e.fontFamily!==null){if(typeof e.fontFamily!="string"||!me.test(e.fontFamily))return{error:"A font stack has only family names, spaces, commas, quotes and dashes."};r.fontFamily=e.fontFamily}if(e.shadow!==void 0&&e.shadow!==null){if(!Object.hasOwn(I,e.shadow))return{error:`Unknown shadow "${e.shadow}". Available: ${Object.keys(I).join(", ")}.`};r.shadow=e.shadow}return{theme:r}}const G=/^[\p{L}\p{N} ._-]{1,60}$/u,W=/^[A-Za-z0-9 ,'"-]{1,200}$/,X=50,y=e=>JSON.parse(JSON.stringify(e)),Y=(e,i)=>JSON.stringify(e)===JSON.stringify(i);function pe(e,i){return e.replace(/\/\*[\s\S]*?\*\//g,"").replace(/([^{}]+)\{/g,(r,t)=>{const a=t.split(",").map(s=>s.trim()).filter(Boolean).map(s=>s===":root"?i:s.startsWith("[data-bs-theme")?`${i}${s}`:`${i} ${s}`);return`${t.match(/^\s*/)[0]}${a.join(`,
`)} {`}).trim()}function ve(e){var i;try{const r=new CSSStyleSheet;return document.adoptedStyleSheets=[...document.adoptedStyleSheets,r],t=>r.replaceSync(t)}catch{const r=document.createElement("style"),t=(i=document.querySelector('meta[name="csp-nonce"]'))==null?void 0:i.getAttribute("content");return t&&(r.nonce=t),r.dataset.celerikThemePreview=e,document.head.append(r),a=>{r.textContent=a}}}function ye(e){const i=()=>[...document.querySelectorAll('link[rel="stylesheet"]')],r=s=>new URL(s.href,window.location.href).pathname.split("/").pop(),t=i().find(s=>r(s)==="celerik.css"),a=e??(t==null?void 0:t.href.replace(/celerik\.css([?#].*)?$/,"").replace(/\/$/,""));return s=>{for(const n of["material","tailwind"]){let u=i().find(m=>r(m)===`celerik-${n}.css`);!u&&s===n&&a&&(u=document.createElement("link"),u.rel="stylesheet",u.href=`${a}/celerik-${n}.css`,(t??document.head.lastChild).after(u)),u&&(u.disabled=s!==n)}}}function ke(e,i){const r=i.labels??{},t=i.pairs??[],a=i.presets??{},s=U(i.theme).theme;if(!s)throw new TypeError(`Celerik: invalid theme for #${e.id}.`);let n=y(s),u=y(n);const m=[],p=new Set,d=o=>e.querySelector(o),k=d(".celerik-theme-builder__canvas"),v=o=>d(`[name="theme[${o}]"]`),f=v("name"),O=v("base"),E=v("radius"),R=d(`#${e.id}-radius-value`),$=v("fontFamily"),L=v("shadow"),C=d('[data-celerik-action="undo"]'),T=d(".celerik-theme-builder__summary"),ee=d(".celerik-theme-builder__report ul"),re=ve(e.id),te=ye(i.assetsUrl),oe=`#${e.id} .celerik-theme-builder__canvas`;function N(o,c){o.setAttribute("aria-invalid","true");let l=document.getElementById(`${o.id}-error`);l||(l=document.createElement("div"),l.id=`${o.id}-error`,l.className="invalid-feedback d-block",(o.closest(".input-group")??o).after(l),o.setAttribute("aria-describedby",`${o.getAttribute("aria-describedby")??""} ${l.id}`.trim())),l.textContent=c}function A(o){o.removeAttribute("aria-invalid");const c=document.getElementById(`${o.id}-error`);if(c){c.remove();const l=(o.getAttribute("aria-describedby")??"").split(" ").filter(b=>b&&b!==c.id);l.length?o.setAttribute("aria-describedby",l.join(" ")):o.removeAttribute("aria-describedby")}}function ne(){const o=le(n,t);ee.replaceChildren(...o.map(b=>{const g=document.createElement("li");g.className=`celerik-theme-builder__pair ${b.passes?"is-pass":"is-fail"}`,g.dataset.celerikPair=`${b.mode}.${b.foreground}.${b.background}`;const M=g.appendChild(document.createElement("span"));M.className=`badge ${b.passes?"text-bg-success":"text-bg-danger"}`,M.textContent=b.passes?r.pass:r.fail;const ie=b.mode==="light"?r.modeLight:r.modeDark;return g.append(` ${ie} · ${b.label}: ${de(b.ratio)}:1 (${r.needs} ${b.minimum}:1)`),g}));const c=o.filter(b=>!b.passes).length,l=c===0?r.allPass:c===1?r.oneFails:r.manyFail.replace("{count}",String(c));T.textContent!==l&&(T.textContent=l),T.classList.toggle("text-success-emphasis",c===0),T.classList.toggle("text-danger-emphasis",c>0)}function h(){re(pe(z(n),oe)),te(n.base),ne();for(const o of p)o(y(n))}function _(){f.value=n.name,O.value=n.base,E.value=String(n.radius??.375),R.textContent=String(n.radius??.375),$.value=n.fontFamily??"",L.value=n.shadow??"";for(const o of q)for(const c of x)for(const l of e.querySelectorAll(`[data-celerik-token="${o}.${c}"]`))l.value=n[o][c];for(const o of e.querySelectorAll('[aria-invalid="true"]'))A(o);C.disabled=m.length===0}function S(){Y(n,u)||(m.push(u),m.length>X&&m.shift(),u=y(n)),C.disabled=m.length===0}function j(o){const{theme:c,error:l}=U(o);if(!c)throw new TypeError(l);Y(c,n)||(m.push(y(n)),m.length>X&&m.shift()),n=c,u=y(n),_(),h()}for(const o of e.querySelectorAll("[data-celerik-token]")){const[c,l]=o.dataset.celerikToken.split(".");o.addEventListener("input",()=>{const b=o.value.trim();if(B(b)){n[c][l]=b.toLowerCase();for(const g of e.querySelectorAll(`[data-celerik-token="${c}.${l}"]`))g!==o&&(g.value=n[c][l]);o.type==="text"&&A(o),h()}}),o.addEventListener("change",()=>{if(o.type==="text"&&!B(o.value.trim())){N(o,r.invalidColor);return}S()})}f.addEventListener("input",()=>{G.test(f.value)&&(n.name=f.value,A(f))}),f.addEventListener("change",()=>{if(!G.test(f.value)){N(f,f.validationMessage||r.name);return}h(),S()}),O.addEventListener("change",()=>{n.base=O.value,h(),S()}),E.addEventListener("input",()=>{n.radius=Number(E.value),R.textContent=E.value,h()}),E.addEventListener("change",S),$.addEventListener("input",()=>{const o=$.value.trim();if(o==="")delete n.fontFamily;else if(W.test(o))n.fontFamily=o;else return;A($),h()}),$.addEventListener("change",()=>{const o=$.value.trim();if(o!==""&&!W.test(o)){N($,r.fontHelp);return}S()}),L.addEventListener("change",()=>{L.value===""?delete n.shadow:n.shadow=L.value,h(),S()});const J=d(`#${e.id}-preset`);d(".celerik-theme-builder__preset").hidden=!1,d('[data-celerik-action="preset"]').addEventListener("click",()=>{a[J.value]&&j(y(a[J.value]))}),d(".celerik-theme-builder__history").hidden=!1,C.addEventListener("click",()=>{m.length!==0&&(n=m.pop(),u=y(n),_(),h(),m.length===0&&f.focus())}),d('[data-celerik-action="reset"]').addEventListener("click",()=>j(y(s))),d(".celerik-theme-builder__modes").hidden=!1;for(const o of e.querySelectorAll(`input[name="${e.id}-preview-mode"]`))o.addEventListener("change",()=>{o.checked&&(k.dataset.bsTheme=o.value)});_(),h();const H={getTheme:()=>y(n),setTheme:j,toCss:()=>z(n),onChange(o){return p.add(o),()=>p.delete(o)}};return ge(e,i,H),H}function Z(e,i){return`${e.normalize("NFD").replace(/[̀-ͯ]/g,"").toLowerCase().replace(/[^a-z0-9]+/g,"-").replace(/^-+|-+$/g,"")||"theme"}.${i}`}function V(e){return`${JSON.stringify(e,null,4)}
`}function he(e){return`use Celerik\\Ui\\Theme\\Theme;

$theme = Theme::fromJson(<<<'JSON'
${V(e)}JSON);
`}function Q(e,i,r){const t=URL.createObjectURL(new Blob([r],{type:i})),a=document.createElement("a");a.href=t,a.download=e,a.hidden=!0,document.body.append(a),a.click(),a.remove(),setTimeout(()=>URL.revokeObjectURL(t),1e3)}function ge(e,i,r){const t=i.labels??{},a=e.querySelector(".celerik-theme-builder__message"),s=(p,d=!1)=>{a.textContent=p,a.classList.toggle("text-danger-emphasis",d)},n=p=>e.querySelector(`[data-celerik-action="${p}"]`);e.querySelector(".celerik-theme-builder__export").hidden=!1,n("download-css").addEventListener("click",()=>{Q(Z(r.getTheme().name,"css"),"text/css",r.toCss())}),n("download-json").addEventListener("click",()=>{Q(Z(r.getTheme().name,"json"),"application/json",V(r.getTheme()))}),n("copy-php").addEventListener("click",async()=>{try{await navigator.clipboard.writeText(he(r.getTheme())),s(t.copied)}catch{s(t.copyFailed,!0)}});const u=n("import");u.addEventListener("change",async()=>{const[p]=u.files;if(u.value="",!p)return;let d;try{d=JSON.parse(await p.text())}catch(f){s(t.importError.replace("{error}",f.message),!0);return}const{theme:k,error:v}=U(d);if(!k){s(t.importError.replace("{error}",v),!0);return}r.setTheme(k),s(t.imported)});const m=e.querySelector("form");i.saveUrl&&m.addEventListener("submit",async p=>{var v;p.preventDefault();const d={"Content-Type":"application/json",Accept:"application/json"},k=i.csrf?(v=m.querySelector(`input[name="${i.csrf.field}"]`))==null?void 0:v.value:null;k&&(d["X-CSRF-TOKEN"]=k);try{const f=await fetch(i.saveUrl,{method:"POST",headers:d,credentials:"same-origin",body:JSON.stringify({theme:r.getTheme()})});s(f.ok?t.saved:t.saveFailed,!f.ok)}catch{s(t.saveFailed,!0)}})}export{Z as fileName,ke as initThemeBuilder,he as phpSnippet,pe as scopeCss,V as themeJson};
//# sourceMappingURL=themebuilder-EONSXa2x.js.map
