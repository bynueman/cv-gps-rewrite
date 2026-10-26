import assert from 'node:assert/strict';
import {after, test} from 'node:test';
import {mkdtemp, realpath, rm} from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath, pathToFileURL} from 'node:url';
import {build} from 'esbuild';

const root=fileURLToPath(new URL('../../',import.meta.url));
const parent=await realpath(path.join(root,'storage/app/private'));
const directory=await mkdtemp(path.join(parent,'story-launch-ui-tests-'));
after(async()=>{const target=await realpath(directory);assert.equal(path.dirname(target),parent);assert.ok(path.basename(target).startsWith('story-launch-ui-tests-'));await rm(target,{recursive:true});});
const output=path.join(directory,'ui.mjs');
await build({
  stdin:{contents:`
    import React from 'react';
    import {renderToStaticMarkup} from 'react-dom/server';
    import {App} from '@inertiajs/react';
    import Home from '@/Pages/Home';
    import Kuicip from '@/Pages/Products/KuicipCatalog';
    import Teko from '@/Pages/Products/TekoCatalog';
    import Detail from '@/Pages/Products/ProductDetail';
    import Cerita from '@/Pages/Kuicip/Cerita';
    import {Sidebar} from '@/Components/admin/Sidebar';
    export function render(component,props){
      const Page={Home,Kuicip,Teko,Detail,Cerita,Sidebar}[component];
      return renderToStaticMarkup(<App initialPage={{component,url:'/',version:null,props}} initialComponent={Page} resolveComponent={()=>Page}/>);
    }
  `,resolveDir:root,sourcefile:'story-launch-test.tsx',loader:'tsx'},
  alias:{'@':path.join(root,'resources/js')},jsx:'automatic',bundle:true,packages:'external',platform:'node',format:'esm',outfile:output,logLevel:'silent',
});
const ui=await import(pathToFileURL(output).href);
globalThis.route=name=>name==='admin.stories.index'?'/admin/stories':'/admin/'+name.split('.')[1];
const brand=key=>({key,name:key==='kuicip'?'Kuicip':'Putri Teko',href:'/products/'+key,tag:{id:'Produk asli',en:'Original products'}});
const company={name:'GPS FOOD',location:{id:'Yogyakarta',en:'Yogyakarta'},email:'test@example.test',whatsapp:'628123456789',whatsapp_href:'https://wa.me/628123456789'};
const site={company,brandAssets:{logos:{gps:'/images/logo/logo-gpsfood.png'},group_shots:{kuicip:'/images/kuicip-nobg.webp','putri-teko':'/images/putriteko-nobg.webp'},fostered_by:[]},brands:[brand('kuicip'),brand('putri-teko')],storyEnabled:true,localPreview:false};
const product={brand:'kuicip',slug:'original',name:{id:'Original',en:'Original'},short:{id:'Deskripsi produk',en:'Product description'},personality:{id:'Renyah',en:'Crunchy'},description:{id:'Produk asli',en:'Original product'},highlights:[],color:'#ffffff',colorDark:'#000000',image:'/images/kuicip/kuicip-ori.webp'};
const props={site,auth:{user:null},seo:{title:'Test',description:'Test',canonical:'https://example.test/'},kuicipCount:8,tekoCount:14,featuredHero:null,featuredSupporting:[],familiesKuicip1:null,familiesKuicip2:null,familiesTeko1:null,familiesTeko2:null,featuredArticles:[],partners:[],products:[product],product,related:[]};
function render(component,values={}){
  const previous=console.error;
  const errors=[];
  console.error=(message,...args)=>{if(typeof message==='string'&&message.includes('useLayoutEffect does nothing on the server'))return;errors.push(message);previous(message,...args);};
  try{const html=ui.render(component,{...props,...values});assert.deepEqual(errors,[]);return html;}finally{console.error=previous;}
}
test('homepage, both catalogs and detail pages retain shared branding with no story campaign links',()=>{
  for(const enabled of [false,true]){
    for(const component of ['Home','Kuicip','Teko','Detail']){
      const html=render(component,{site:{...site,storyEnabled:enabled}});
      assert.ok(!/href="[^"]*\/kuicip\/cerita/.test(html),component);
      assert.ok(!html.includes('Ruang Cerita'),component);
      assert.ok(html.includes('<header'),component);
      assert.ok(html.includes('<footer'),component);
      assert.ok(html.includes('/images/logo/logo-gpsfood.png'),component);
    }
  }
});
test('moderation sidebar entry is shown only to superadmins while story management is enabled',()=>{
  for(const role of ['superadmin','editor']){
    for(const enabled of [false,true]){
      const html=render('Sidebar',{site:{...site,storyEnabled:enabled},auth:{user:{role}},mobileOpen:false,onCloseMobile:()=>{},collapsed:false,onToggleCollapse:()=>{}});
      assert.equal(html.includes('href="/admin/stories"'),role==='superadmin'&&enabled);
      if(role==='superadmin'&&enabled)assert.ok(html.includes('Kuicip — Ruang Cerita'));
    }
  }
});
test('direct story page keeps the corporate chrome and gates its form and feed in Stage A',()=>{
  const base={siteKey:null,policyVersion:'draft-v1',retentionDays:90,categories:['kehidupan']};
  const closed=render('Cerita',{...base,enabled:false});
  assert.ok(closed.includes('Ruang Cerita belum dibuka.'));
  assert.ok(!closed.includes('<textarea'));
  const open=render('Cerita',{...base,enabled:true,siteKey:'fixture-site-key',policyVersion:'approved-fixture'});
  assert.ok(open.includes('<textarea'));
  assert.ok(open.includes('name="publication_consent"'));
  assert.ok(open.includes('name="social_consent"'));
  assert.ok(open.includes('name="terms_consent"'));
  assert.ok(open.includes('name="token" type="password"'));
  assert.ok(open.includes('<header'));
  assert.ok(open.includes('<footer'));
  assert.ok(open.includes('90'));
  assert.ok(open.includes('mailto:test@example.test'));
});
