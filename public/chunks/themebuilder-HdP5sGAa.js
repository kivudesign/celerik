const P=["bootstrap","material","tailwind"],B=["light","dark"],$=["primary","primaryHover","onPrimary","bodyBg","bodyColor","muted","surface","border","inputBorder","danger"],N={none:"none",subtle:"0 1px 2px rgba(0, 0, 0, 0.08)",elevated:"0 1px 2px rgba(0, 0, 0, 0.3), 0 1px 3px 1px rgba(0, 0, 0, 0.15)",outlined:"0 1px 2px 0 rgba(0, 0, 0, 0.05), 0 0 0 1px var(--bs-border-color)"},te={bootstrap:"0.25",material:"0.35",tailwind:"0.5"},oe=`.btn-primary {
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
}`;function k(e){return[1,3,5].map(i=>parseInt(e.slice(i,i+2),16)).join(", ")}function F(e,i){return`${e} {
${Object.entries(i).map(([r,n])=>`  ${r}: ${n};
`).join("")}}
`}function R(e,i){const r=e[i],n={"--bs-primary":r.primary,"--bs-primary-rgb":k(r.primary),"--bs-link-color":r.primary,"--bs-link-color-rgb":k(r.primary),"--bs-link-hover-color":r.primaryHover,"--bs-link-hover-color-rgb":k(r.primaryHover),"--bs-primary-text-emphasis":r.primaryHover,"--bs-body-bg":r.bodyBg,"--bs-body-bg-rgb":k(r.bodyBg),"--bs-body-color":r.bodyColor,"--bs-body-color-rgb":k(r.bodyColor),"--bs-secondary-color":r.muted,"--bs-tertiary-bg":r.surface,"--bs-tertiary-bg-rgb":k(r.surface),"--bs-border-color":r.border,"--bs-focus-ring-color":`rgba(${k(r.primary)}, ${te[e.base]})`,"--bs-form-invalid-color":r.danger,"--bs-form-invalid-border-color":r.danger,"--bs-danger-text-emphasis":r.danger,"--celerik-primary":r.primary,"--celerik-on-primary":r.onPrimary,"--celerik-primary-hover":r.primaryHover,"--celerik-input-border":r.inputBorder};return e.base==="tailwind"&&(n["--celerik-grid-header-color"]=r.muted,n["--celerik-focus-ring"]=`0 0 0 2px ${r.bodyBg}, 0 0 0 4px ${r.primary}`),n}function U(e){const i=e.base==="bootstrap"?"celerik.css":`celerik.css and celerik-${e.base}.css`;let r=`/* Celerik UI theme "${e.name}" (base: ${e.base}). Load it after ${i}. */
`;r+=F(`:root,
[data-bs-theme=light]`,R(e,"light")),r+=`
${F("[data-bs-theme=dark]",R(e,"dark"))}`;const n={};if(e.radius!==void 0&&e.radius!==null){const a=`${Math.round(e.radius*1e3)/1e3}rem`;n["--bs-border-radius"]=a,n["--celerik-radius"]=a}return e.fontFamily&&(n["--bs-body-font-family"]=e.fontFamily),e.shadow&&(n["--celerik-surface-shadow"]=N[e.shadow]),Object.keys(n).length>0&&(r+=`
${F(":root",n)}`),e.base==="bootstrap"&&(r+=`
${oe}
`),r}function J(e){const[i,r,n]=[1,3,5].map(a=>parseInt(e.slice(a,a+2),16)/255).map(a=>a<=.03928?a/12.92:((a+.055)/1.055)**2.4);return .2126*i+.7152*r+.0722*n}function ne(e,i){const[r,n]=[J(e),J(i)].sort((a,s)=>s-a);return(r+.05)/(n+.05)}function ie(e,i){return B.flatMap(r=>i.map(n=>{const a=ne(e[r][n.foreground],e[r][n.background]);return{...n,mode:r,ratio:a,passes:a>=n.minimum}}))}function ae(e){return(Math.floor(e*100)/100).toFixed(2)}const se=/^#[0-9a-f]{6}$/i,ce=/^[\p{L}\p{N} ._-]{1,60}$/u,le=/^[A-Za-z0-9 ,'"-]{1,200}$/,de=["name","base","light","dark","radius","fontFamily","shadow"];function I(e){return typeof e=="string"&&se.test(e)}function D(e){if(e===null||typeof e!="object"||Array.isArray(e))return{error:"A theme must be a JSON object."};const i=Object.keys(e).filter(n=>!de.includes(n));if(i.length)return{error:`Unknown theme keys: ${i.join(", ")}.`};if(typeof e.name!="string"||!ce.test(e.name))return{error:"A theme name has 1 to 60 letters, digits, spaces, dots, dashes or underscores."};if(!P.includes(e.base))return{error:`Unknown base "${e.base}". Available: ${P.join(", ")}.`};const r={name:e.name,base:e.base};for(const n of B){const a=e[n];if(a===null||typeof a!="object"||Array.isArray(a))return{error:`The ${n} palette must be an object.`};const s=Object.keys(a);if(s.length!==$.length||!$.every(o=>s.includes(o)))return{error:`The ${n} palette needs exactly these colors: ${$.join(", ")}.`};for(const o of $)if(!I(a[o]))return{error:`Invalid color "${a[o]}" for ${n}.${o}: use #rrggbb.`};r[n]=Object.fromEntries($.map(o=>[o,a[o].toLowerCase()]))}if(e.radius!==void 0&&e.radius!==null){if(typeof e.radius!="number"||e.radius<0||e.radius>2)return{error:"The radius must be a number between 0 and 2 (rem)."};r.radius=Math.round(e.radius*1e3)/1e3}if(e.fontFamily!==void 0&&e.fontFamily!==null){if(typeof e.fontFamily!="string"||!le.test(e.fontFamily))return{error:"A font stack has only family names, spaces, commas, quotes and dashes."};r.fontFamily=e.fontFamily}if(e.shadow!==void 0&&e.shadow!==null){if(!Object.hasOwn(N,e.shadow))return{error:`Unknown shadow "${e.shadow}". Available: ${Object.keys(N).join(", ")}.`};r.shadow=e.shadow}return{theme:r}}const z=/^[\p{L}\p{N} ._-]{1,60}$/u,G=/^[A-Za-z0-9 ,'"-]{1,200}$/,K=50,p=e=>JSON.parse(JSON.stringify(e)),W=(e,i)=>JSON.stringify(e)===JSON.stringify(i);function be(e,i){return e.replace(/\/\*[\s\S]*?\*\//g,"").replace(/([^{}]+)\{/g,(r,n)=>{const a=n.split(",").map(s=>s.trim()).filter(Boolean).map(s=>s===":root"?i:s.startsWith("[data-bs-theme")?`${i}${s}`:`${i} ${s}`);return`${n.match(/^\s*/)[0]}${a.join(`,
`)} {`}).trim()}function ue(e){var i;try{const r=new CSSStyleSheet;return document.adoptedStyleSheets=[...document.adoptedStyleSheets,r],n=>r.replaceSync(n)}catch{const r=document.createElement("style"),n=(i=document.querySelector('meta[name="csp-nonce"]'))==null?void 0:i.getAttribute("content");return n&&(r.nonce=n),r.dataset.celerikThemePreview=e,document.head.append(r),a=>{r.textContent=a}}}function me(e){const i=()=>[...document.querySelectorAll('link[rel="stylesheet"]')],r=s=>new URL(s.href,window.location.href).pathname.split("/").pop(),n=i().find(s=>r(s)==="celerik.css"),a=e??(n==null?void 0:n.href.replace(/celerik\.css([?#].*)?$/,"").replace(/\/$/,""));return s=>{for(const o of["material","tailwind"]){let b=i().find(u=>r(u)===`celerik-${o}.css`);!b&&s===o&&a&&(b=document.createElement("link"),b.rel="stylesheet",b.href=`${a}/celerik-${o}.css`,(n??document.head.lastChild).after(b)),b&&(b.disabled=s!==o)}}}function fe(e,i){const r=i.labels??{},n=i.pairs??[],a=i.presets??{},s=D(i.theme).theme;if(!s)throw new TypeError(`Celerik: invalid theme for #${e.id}.`);let o=p(s),b=p(o);const u=[],L=new Set,m=t=>e.querySelector(t),Y=m(".celerik-theme-builder__canvas"),w=t=>m(`[name="theme[${t}]"]`),f=w("name"),C=w("base"),S=w("radius"),H=m(`#${e.id}-radius-value`),h=w("fontFamily"),x=w("shadow"),O=m('[data-celerik-action="undo"]'),A=m(".celerik-theme-builder__summary"),Z=m(".celerik-theme-builder__report ul"),X=ue(e.id),Q=me(i.assetsUrl),V=`#${e.id} .celerik-theme-builder__canvas`;function _(t,c){t.setAttribute("aria-invalid","true");let l=document.getElementById(`${t.id}-error`);l||(l=document.createElement("div"),l.id=`${t.id}-error`,l.className="invalid-feedback d-block",(t.closest(".input-group")??t).after(l),t.setAttribute("aria-describedby",`${t.getAttribute("aria-describedby")??""} ${l.id}`.trim())),l.textContent=c}function E(t){t.removeAttribute("aria-invalid");const c=document.getElementById(`${t.id}-error`);if(c){c.remove();const l=(t.getAttribute("aria-describedby")??"").split(" ").filter(d=>d&&d!==c.id);l.length?t.setAttribute("aria-describedby",l.join(" ")):t.removeAttribute("aria-describedby")}}function ee(){const t=ie(o,n);Z.replaceChildren(...t.map(d=>{const y=document.createElement("li");y.className=`celerik-theme-builder__pair ${d.passes?"is-pass":"is-fail"}`,y.dataset.celerikPair=`${d.mode}.${d.foreground}.${d.background}`;const M=y.appendChild(document.createElement("span"));M.className=`badge ${d.passes?"text-bg-success":"text-bg-danger"}`,M.textContent=d.passes?r.pass:r.fail;const re=d.mode==="light"?r.modeLight:r.modeDark;return y.append(` ${re} · ${d.label}: ${ae(d.ratio)}:1 (${r.needs} ${d.minimum}:1)`),y}));const c=t.filter(d=>!d.passes).length,l=c===0?r.allPass:c===1?r.oneFails:r.manyFail.replace("{count}",String(c));A.textContent!==l&&(A.textContent=l),A.classList.toggle("text-success-emphasis",c===0),A.classList.toggle("text-danger-emphasis",c>0)}function v(){X(be(U(o),V)),Q(o.base),ee();for(const t of L)t(p(o))}function T(){f.value=o.name,C.value=o.base,S.value=String(o.radius??.375),H.textContent=String(o.radius??.375),h.value=o.fontFamily??"",x.value=o.shadow??"";for(const t of B)for(const c of $)for(const l of e.querySelectorAll(`[data-celerik-token="${t}.${c}"]`))l.value=o[t][c];for(const t of e.querySelectorAll('[aria-invalid="true"]'))E(t);O.disabled=u.length===0}function g(){W(o,b)||(u.push(b),u.length>K&&u.shift(),b=p(o)),O.disabled=u.length===0}function j(t){const{theme:c,error:l}=D(t);if(!c)throw new TypeError(l);W(c,o)||(u.push(p(o)),u.length>K&&u.shift()),o=c,b=p(o),T(),v()}for(const t of e.querySelectorAll("[data-celerik-token]")){const[c,l]=t.dataset.celerikToken.split(".");t.addEventListener("input",()=>{const d=t.value.trim();if(I(d)){o[c][l]=d.toLowerCase();for(const y of e.querySelectorAll(`[data-celerik-token="${c}.${l}"]`))y!==t&&(y.value=o[c][l]);t.type==="text"&&E(t),v()}}),t.addEventListener("change",()=>{if(t.type==="text"&&!I(t.value.trim())){_(t,r.invalidColor);return}g()})}f.addEventListener("input",()=>{z.test(f.value)&&(o.name=f.value,E(f))}),f.addEventListener("change",()=>{if(!z.test(f.value)){_(f,f.validationMessage||r.name);return}v(),g()}),C.addEventListener("change",()=>{o.base=C.value,v(),g()}),S.addEventListener("input",()=>{o.radius=Number(S.value),H.textContent=S.value,v()}),S.addEventListener("change",g),h.addEventListener("input",()=>{const t=h.value.trim();if(t==="")delete o.fontFamily;else if(G.test(t))o.fontFamily=t;else return;E(h),v()}),h.addEventListener("change",()=>{const t=h.value.trim();if(t!==""&&!G.test(t)){_(h,r.fontHelp);return}g()}),x.addEventListener("change",()=>{x.value===""?delete o.shadow:o.shadow=x.value,v(),g()});const q=m(`#${e.id}-preset`);m(".celerik-theme-builder__preset").hidden=!1,m('[data-celerik-action="preset"]').addEventListener("click",()=>{a[q.value]&&j(p(a[q.value]))}),m(".celerik-theme-builder__history").hidden=!1,O.addEventListener("click",()=>{u.length!==0&&(o=u.pop(),b=p(o),T(),v(),u.length===0&&f.focus())}),m('[data-celerik-action="reset"]').addEventListener("click",()=>j(p(s))),m(".celerik-theme-builder__modes").hidden=!1;for(const t of e.querySelectorAll(`input[name="${e.id}-preview-mode"]`))t.addEventListener("change",()=>{t.checked&&(Y.dataset.bsTheme=t.value)});return T(),v(),{getTheme:()=>p(o),setTheme:j,toCss:()=>U(o),onChange(t){return L.add(t),()=>L.delete(t)}}}export{fe as initThemeBuilder,be as scopeCss};
//# sourceMappingURL=themebuilder-HdP5sGAa.js.map
