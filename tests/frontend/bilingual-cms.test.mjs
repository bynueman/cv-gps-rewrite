import assert from 'node:assert/strict';
import {after,test} from 'node:test';
import {mkdtemp,realpath,rm} from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath,pathToFileURL} from 'node:url';
import {build} from 'esbuild';

const root=fileURLToPath(new URL('../../',import.meta.url));
const parent=await realpath(path.join(root,'storage/app/private'));
const directory=await mkdtemp(path.join(parent,'bilingual-ui-tests-'));
after(async()=>{const target=await realpath(directory);assert.equal(path.dirname(target),parent);assert.ok(path.basename(target).startsWith('bilingual-ui-tests-'));await rm(target,{recursive:true});});
const output=path.join(directory,'ui.mjs');
await build({
  stdin:{contents:`
    import React from 'react';
    import {renderToStaticMarkup} from 'react-dom/server';
    import {App} from '@inertiajs/react';
    import {LanguageProvider} from '@/lib/i18n';
    import {LocalizedList,LocalizedText,localeValue} from '@/lib/localized';
    import {BilingualListEditor} from '@/Components/admin/BilingualListEditor';
    import Home from '@/Pages/Home';
    import Kuicip from '@/Pages/Products/KuicipCatalog';
    import Teko from '@/Pages/Products/TekoCatalog';
    import Detail from '@/Pages/Products/ProductDetail';
    import News from '@/Pages/News/Show';
    import Partners from '@/Pages/Partners/Index';
    import {Header} from '@/Components/Header';
    import ProductForm from '@/Pages/Admin/Products/Form';
    export {BilingualListEditor,localeValue};
    export const list=(items,lang)=>renderToStaticMarkup(<LocalizedList items={items} lang={lang} color="#000000"/>);
    export const text=(value,lang)=>renderToStaticMarkup(<LocalizedText value={value} lang={lang}/>);
    function StoredLanguage({lang,children}){
      const previous=globalThis.window;
      // Seed only the persisted-language initializer; children retain normal server globals.
      globalThis.window={localStorage:{getItem:()=>lang}};
      try{return LanguageProvider({children});}
      finally{if(previous===undefined)delete globalThis.window;else globalThis.window=previous;}
    }
    export function render(name,props,lang){
      const Page={Home,Kuicip,Teko,Detail,News,Partners,Header,ProductForm}[name];
      function Wrapped(values){return <StoredLanguage lang={lang}><Page {...values}/></StoredLanguage>;}
      return renderToStaticMarkup(<App initialPage={{component:name,url:props.url||'/',version:null,props}} initialComponent={Wrapped} resolveComponent={()=>Wrapped}/>);
    }
  `,resolveDir:root,sourcefile:'bilingual-ui.tsx',loader:'tsx'},
  alias:{'@':path.join(root,'resources/js')},jsx:'automatic',bundle:true,packages:'external',platform:'node',format:'esm',outfile:output,logLevel:'silent',
});
const ui=await import(pathToFileURL(output).href);
globalThis.route=(name,id)=>'/admin/'+name.split('.')[1]+(typeof id==='number'?'/'+id:'');
const notes=[
  {key:'a',id:'Gurih asin, ringan',en:'Light and savoury'},
  {key:'b',id:'Cocok untuk semua umur',en:'Suitable for all ages'},
  {key:'c',id:'No MSG',en:''},{key:'d',id:'Not Oily',en:''},{key:'e',id:'Gluten Free',en:''},{key:'f',id:'Tanpa Pengawet',en:''},
];
const product={slug:'original',brand:'kuicip',name:{id:'Original (NO MSG)',en:'Original'},short:{id:'Camilan singkong',en:'Cassava snack'},personality:{id:'Gurih',en:'Savoury'},description:{id:'Camilan singkong renyah.',en:'Crispy cassava snack.'},notes,highlights:[],color:'#E29A12',colorDark:'#9C6508',image:'/images/kuicip/kuicip-ori.webp'};
const brand=key=>({key,name:key==='kuicip'?'Kuicip':'Putri Teko',href:'/products/'+key,tag:{id:'Produk lokal',en:'Local products'},story:{id:'Cerita merek',en:'Brand story'},weight:'70 gr'});
const article={slug:'company',title:{id:'Cerita perusahaan',en:'Company story'},excerpt:{id:'Ringkasan perusahaan',en:'Company summary'},body:[{id:'<p>Cerita Indonesia.</p>',en:'<p>Company story.</p>'}],category:{id:'Perusahaan',en:'Company'},date:'2026-10-26T00:00:00Z',tags:[],image:null};
const site={company:{name:'GPS FOOD',location:{id:'Yogyakarta',en:'Yogyakarta'},hours:{id:'Senin',en:'Monday'},email:'test@example.test',whatsapp:'628123456789',whatsapp_href:'https://wa.me/628123456789'},brandAssets:{logos:{gps:'/images/logo/logo-gpsfood.png'},group_shots:{kuicip:'/images/kuicip-nobg.webp','putri-teko':'/images/putriteko-nobg.webp'},fostered_by:[]},brands:[brand('kuicip'),brand('putri-teko')],storyEnabled:true,localPreview:false};
const base={site,auth:{user:{role:'superadmin'}},seo:{title:'Test',description:'Test',canonical:'https://example.test/'},seoEnglish:{title:'English title',description:'English description'},product,products:[product],related:[],article,relatedArticles:[],kuicipCount:8,tekoCount:14,featuredHero:null,featuredSupporting:[],familiesKuicip1:null,familiesKuicip2:null,familiesTeko1:null,familiesTeko2:null,featuredArticles:[],partners:[],filters:{}};
function render(name,lang='id',props={}){
  const original=console.error;const errors=[];
  console.error=(message,...args)=>{if(typeof message==='string'&&message.includes('useLayoutEffect does nothing on the server'))return;errors.push(message);};
  try{const html=ui.render(name,{...base,...props},lang);assert.deepEqual(errors,[]);return html;}finally{console.error=original;}
}
function nodes(node){if(Array.isArray(node))return node.flatMap(nodes);if(!node||typeof node!=='object'||!node.props)return[];return[node,...nodes(node.props.children)];}
function text(node){if(typeof node==='string'||typeof node==='number')return String(node);if(Array.isArray(node))return node.map(text).join('');return node?.props?text(node.props.children):'';}

test('six ID notes and two EN notes render six nonempty bullets with explicit source-language fallbacks',()=>{
  const html=ui.list(notes,'en');
  assert.equal((html.match(/<li /g)||[]).length,6);
  assert.ok(html.includes('Light and savoury'));
  for(const item of notes.slice(2))assert.ok(html.includes(item.id));
  assert.equal((html.match(/\[ID\]/g)||[]).length,4);
  assert.ok(html.includes('lang="id"'));
  const complete=ui.list(notes.map(item=>({...item,en:item.en||(item.id==='Tanpa Pengawet'?'No preservatives':item.id)})),'en');
  assert.equal((complete.match(/<li /g)||[]).length,6);
});

test('blank rows do not render decorative bullets and stale English does not erase source facts',()=>{
  assert.ok(!ui.list([{id:' ',en:''}],'en').includes('<li'));
  const html=ui.text({id:'Original (NO MSG)',en:'Original'},'en');
  assert.ok(html.includes('Original (NO MSG)'));
  assert.ok(html.includes('[ID]'));
  assert.equal(ui.localeValue({id:'Berat baru',en:'Old weight',en_stale:true},'en').text,'Berat baru');
  assert.ok(ui.text({id:'<script>alert(1)</script>',en:''},'en').includes('&lt;script&gt;'));
});

test('actual list controls reorder and remove keyed pairs without shifting English',()=>{
  let items=notes.slice(0,3).map(item=>({...item}));
  const props=()=>({label:'Notes',items,onChange:next=>{items=next;}});
  let tree=ui.BilingualListEditor(props());
  nodes(tree).find(node=>node.props['aria-label']==='Naikkan poin 3').props.onClick();
  assert.deepEqual(items.map(item=>item.key),['a','c','b']);
  assert.equal(items.find(item=>item.key==='b').en,'Suitable for all ages');
  tree=ui.BilingualListEditor(props());
  nodes(tree).find(node=>node.type==='button'&&text(node)==='Hapus poin 2').props.onClick();
  assert.deepEqual(items.map(item=>item.key),['a','b']);
  tree=ui.BilingualListEditor(props());
  nodes(tree).find(node=>node.type==='button'&&text(node)==='Tambah poin').props.onClick();
  assert.ok(items[2].key&&items[2].key!=='a'&&items[2].en==='');
});

test('major public routes use the stored language for dictionaries and already-stored content',()=>{
  assert.ok(render('Home','en').includes('Every Flavour Has a Story.'));
  assert.ok(render('Header','en').includes('Story Space'));
  assert.ok(render('Header','id').includes('Ruang Cerita'));
  assert.ok(render('Detail','en').includes('Crispy cassava snack.'));
  assert.ok(render('Kuicip','en').includes('Cassava snack'));
  assert.ok(render('Teko','en',{products:[{...product,brand:'putri-teko',packaging:'botol'}]}).includes('Cassava snack'));
  assert.ok(render('News','en').includes('Company summary'));
  assert.ok(render('News','id').includes('Ringkasan perusahaan'));
  assert.ok(render('Partners','en').includes('<h1'));
});

test('Indonesia-first product form exposes optional English and stable item controls',()=>{
  const formProduct={...product,id:1,brand_id:1,group:'',packaging:'',color_dark:product.colorDark,serving:{id:'',en:''},shopee_url:'',net_weight:'',seo_title_id:'',seo_title_en:'',meta_description_id:'',meta_description_en:'',image_alt_id:'',image_alt_en:'',featured:false,placeholder:false,is_active:true,sort_order:0};
  const html=render('ProductForm','id',{mode:'edit',brands:[{id:1,key:'kuicip',name:'Kuicip'}],product:formProduct});
  assert.ok(html.includes('Konten Indonesia'));
  assert.ok(html.includes('Terjemahan English'));
  assert.ok(html.includes('Simpan sekali'));
  assert.ok(html.includes('Naikkan poin 3'));
  assert.ok(html.includes('Hapus poin 6'));
  assert.ok(!html.includes('Terjemahkan ke Indonesia'));
});
