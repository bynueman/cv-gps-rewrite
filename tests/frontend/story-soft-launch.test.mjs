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
    import StoryIndex from '@/Pages/Admin/Stories/Index';
    import StoryShow from '@/Pages/Admin/Stories/Show';
    import {Header} from '@/Components/Header';
    import {LanguageProvider,dictionary} from '@/lib/i18n';
    export {dictionary};
    function HeaderFixture(){return <LanguageProvider><Header/></LanguageProvider>;}
    import {Sidebar} from '@/Components/admin/Sidebar';
    export function render(component,props,url="/"){
      const Page={Home,Kuicip,Teko,Detail,Cerita,Sidebar,StoryIndex,StoryShow,Header:HeaderFixture}[component];
      return renderToStaticMarkup(<App initialPage={{component,url,version:null,props}} initialComponent={Page} resolveComponent={()=>Page}/>);
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
function render(component,values={},url="/"){
  const previous=console.error;
  const errors=[];
  console.error=(message,...args)=>{if(typeof message==='string'&&message.includes('useLayoutEffect does nothing on the server'))return;errors.push(message);previous(message,...args);};
  try{const html=ui.render(component,{...props,...values},url);assert.deepEqual(errors,[]);return html;}finally{console.error=previous;}
}
test('homepage adds one story invitation and shared navigation preserves the corporate pages',()=>{
  for(const enabled of [false,true]){
    for(const component of ['Home','Kuicip','Teko','Detail']){
      const html=render(component,{site:{...site,storyEnabled:enabled}});
      const header=html.match(/<header[\s\S]*?<\/header>/)[0];
      const footer=html.match(/<footer[\s\S]*?<\/footer>/)[0];
      assert.equal((header.match(/href="\/kuicip\/cerita"/g)||[]).length,1,component);
      assert.ok(!footer.includes('/kuicip/cerita'),component);
      assert.equal((html.match(/href="\/kuicip\/cerita"/g)||[]).length,component==='Home'?2:1,component);
      assert.equal(html.includes('id="story-invitation-title"'),component==='Home',component);
      assert.ok(html.includes('/images/logo/logo-gpsfood.png'),component);
      assert.ok(html.includes('href="/products"'),component);
      assert.ok(html.includes('href="/news"'),component);
      assert.ok(html.includes('href="/contact"'),component);
    }
  }
});

test('navbar exposes a first-level story destination with its active state and retained order',()=>{
  const html=render('Header',{},'/kuicip/cerita');
  const nav=html.match(/<nav aria-label="Main"[^>]*>([\s\S]*?)<\/nav>/)[1];
  assert.ok(/<a(?=[^>]*href="\/kuicip\/cerita")(?=[^>]*aria-current="page")[^>]*>Ruang Cerita<\/a>/.test(nav));
  assert.ok(nav.indexOf('href="/products"') < nav.indexOf('href="/kuicip/cerita"'));
  assert.ok(nav.indexOf('href="/kuicip/cerita"') < nav.indexOf('href="/mitra"'));
  for(const text of ['Beranda','Produk Kami','Mitra','Ekspor','Berita &amp; Kegiatan','Hubungi Kami'])assert.ok(nav.includes(text),text);
  assert.ok(html.includes('aria-controls="mobile-nav"'));
  assert.ok(/<button(?=[^>]*aria-controls="mobile-nav")(?=[^>]*aria-expanded="false")/.test(html));
});

test('homepage invitation uses reviewed messaging and follows both product families',()=>{
  const html=render('Home');
  const start=html.indexOf('<section aria-labelledby="story-invitation-title"');
  const invitation=html.slice(start,html.indexOf('</section>',start));
  assert.ok(invitation.includes('Setiap Rasa Punya Cerita.'));
  assert.ok(invitation.includes('Mulai Bercerita'));
  assert.ok(invitation.includes('Tanpa akun'));
  assert.ok(invitation.includes('Cerita ditinjau sebelum ditampilkan'));
  assert.ok(invitation.includes('aria-hidden="true"'));
  assert.ok(!invitation.includes('<img'));
  assert.ok(!invitation.includes('<blockquote'));
  assert.ok(html.indexOf('href="/products/putri-teko"') < start);
  assert.ok(start < html.indexOf('href="/export"',start));
  assert.equal(ui.dictionary.en.nav.stories,'Story Space');
  assert.equal(ui.dictionary.en.homeStory.title,'Every Flavour Has a Story.');
  assert.equal(ui.dictionary.en.homeStory.cta,'Start Sharing');
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
  const base={antispamProvider:'turnstile',siteKey:null,policyVersion:'draft-v1',retentionDays:90,categories:['kehidupan']};
  const closed=render('Cerita',{...base,enabled:false});
  assert.ok(closed.includes('Ruang Cerita sementara belum menerima kiriman.'));
  assert.ok(closed.includes('Cerita yang sudah ada tetap tersimpan.'));
  assert.ok(!closed.includes('Belum ada kiriman yang diterima'));
  assert.ok(!closed.includes('<textarea'));
  const open=render('Cerita',{...base,enabled:true,siteKey:'fixture-site-key',policyVersion:'approved-fixture'});
  assert.ok(open.includes('<textarea'));
  assert.ok(open.includes('name="publication_consent"'));
  assert.ok(open.includes('name="social_consent"'));
  assert.ok(open.includes('name="terms_consent"'));
  assert.ok(!open.includes('name="token" type="password"'));
  assert.ok(open.includes('<header'));
  assert.ok(open.includes('<footer'));
  assert.ok(open.includes('90'));
  assert.ok(open.includes('mailto:test@example.test'));
});

test('local verification needs no Turnstile key and its trap stays outside keyboard and accessibility navigation',()=>{
  const html=render('Cerita',{enabled:true,antispamProvider:'local',siteKey:null,policyVersion:'approved-fixture',retentionDays:90,categories:['kehidupan']});
  assert.ok(html.includes('Verifikasi sederhana'));
  assert.ok(html.includes('Hitung dan tulis hasilnya'));
  assert.ok(html.includes('Minta soal baru'));
  assert.ok(html.includes('name="challenge_answer"'));
  assert.ok(html.includes('inputMode="numeric"'));
  assert.ok(html.includes('aria-describedby="story-verification-help"'));
  assert.ok(html.includes('hidden="" aria-hidden="true"'));
  assert.ok(/name="contact_website"[^>]*tabindex="-1"[^>]*autoComplete="off"/.test(html));
  assert.ok(!html.includes('Verifikasi belum tersedia.'));
  assert.ok(!html.includes('challenges.cloudflare.com'));
  assert.equal((html.match(/href="\/kuicip\/cerita"/g)||[]).length,1);
});
test('an unknown provider renders no usable verification or send button',()=>{
  const html=render('Cerita',{enabled:true,antispamProvider:'none',siteKey:null,policyVersion:'approved-fixture',retentionDays:90,categories:['kehidupan']});
  assert.ok(html.includes('Verifikasi belum tersedia. Coba lagi nanti.'));
  assert.ok(/<button disabled=""[^>]*>Kirim untuk ditinjau/.test(html));
  assert.ok(!html.includes('name="challenge_answer"'));
});

const pagination={prev_page_url:null,next_page_url:null,current_page:1,last_page:1};
test('moderation index and detail render readable states, code search and no removal secrets',()=>{
  const auth={user:{id:1,name:'Test moderator',email:'moderator@example.test',role:'superadmin'}};
  const index=render('StoryIndex',{auth,stories:{...pagination,data:[{id:6,code:'KC-0006',excerpt:'Cerita privat fiksi.',visibility:'private',status:'pending'}]},pendingReports:2,filters:{status:'pending',code:'KC-0006'}});
  assert.ok(index.includes('name="code"'));
  assert.ok(index.includes('value="KC-0006"'));
  assert.ok(index.includes('aria-current="page"'));
  assert.ok(index.includes('Menunggu tinjauan'));
  assert.ok(index.includes('Tinjau cerita'));
  const detail=render('StoryShow',{auth,story:{id:6,body:'Cerita privat fiksi.\n\nParagraf berikutnya.',category:'kehidupan',visibility:'private',status:'pending',publication_consent:false,social_consent:false,policy_version:'approved-fixture'},code:'KC-0006',history:[],reports:{...pagination,data:[{id:2,reason:'privacy',details:null,status:'pending'}]}});
  assert.ok(detail.includes('Salin kode cerita KC-0006'));
  assert.ok(detail.includes('persetujuan moderasi tidak memublikasikan'));
  assert.ok(detail.includes('Keputusan moderasi'));
  assert.ok(detail.includes('Tandai ditinjau'));
  assert.ok(detail.includes('approved-fixture'));
  assert.ok(detail.includes('whitespace-pre-wrap'));
  for(const html of [index,detail]){
    assert.ok(!html.includes('deletion_token'));
    assert.ok(!html.includes('Removal token'));
    assert.ok(!html.includes('name="token"'));
  }
});

test('submission groups preserve consent requirements and block sending before verification',()=>{
  const html=render('Cerita',{enabled:true,antispamProvider:'local',siteKey:null,policyVersion:'approved-fixture',retentionDays:90,categories:['kehidupan','bahagia']});
  assert.ok(/<input(?=[^>]*name="visibility")(?=[^>]*value="public")(?=[^>]*checked="")[^>]*>/.test(html));
  assert.ok(/<input(?=[^>]*name="visibility")(?=[^>]*value="private")[^>]*>/.test(html));
  assert.ok(/name="terms_consent" type="checkbox" required=""/.test(html));
  assert.ok(/name="publication_consent" type="checkbox" required=""/.test(html));
  assert.ok(!/name="social_consent"[^>]*required/.test(html));
  assert.ok(/<button disabled=""[^>]*>Kirim untuk ditinjau/.test(html));
  assert.ok(html.indexOf('name="category"') < html.indexOf('id="story-body"'));
  assert.ok(html.indexOf('id="story-body"') < html.indexOf('name="visibility"'));
  assert.ok(html.includes('minLength="20" maxLength="1500"'));
  assert.ok(html.includes('aria-describedby="story-body-help story-character-count"'));
  assert.ok(html.includes('Cara kerjanya'));
  assert.ok(html.includes('Kamu tidak sendiri.'));
});

test('public story page retires the token form and points to the configured support channel',()=>{
  const html=render('Cerita',{enabled:true,antispamProvider:'local',siteKey:null,policyVersion:'approved-fixture',retentionDays:90,categories:['kehidupan']});
  assert.ok(html.includes('mailto:test@example.test'));
  assert.ok(html.includes('bukan bukti kepemilikan'));
  assert.ok(html.includes('Tim akan meninjau permintaan'));
  for(const old of ['Hapus ceritamu dengan token','Simpan token penghapusan','name="token"','name="code"','story-removal-receipt','deletion_token'])assert.ok(!html.includes(old),old);
  assert.ok(html.includes('name="terms_consent"'));
  assert.ok(html.includes('name="publication_consent"'));
  assert.ok(html.includes('name="social_consent"'));
});

test('redacted admin tombstones preserve audit access and expose no moderation or removal action',()=>{
  const pagination={current_page:1,last_page:1,per_page:20,total:0,from:null,to:null,links:[]};
  const html=render('StoryShow',{auth:{user:{id:1,name:'Test operator',role:'superadmin'}},redacted:true,
    story:{id:6,body:'[removed]',category:'kehidupan',visibility:'private',status:'hidden',publication_consent:false,social_consent:false,policy_version:'historical-policy'},
    code:'KC-0006',history:[{id:1,actor_id:1,action:'privacy_redacted',from_status:'approved',to_status:'hidden',reason:'Operator reviewed privacy request PR-20261027-001',created_at:'2026-10-27'}],reports:{...pagination,data:[]}});
  assert.ok(html.includes('historical-policy'));
  assert.ok(html.includes('PR-20261027-001'));
  assert.ok(html.includes('tidak dapat dipublikasikan kembali'));
  assert.ok(!html.includes('Keputusan moderasi'));
  assert.ok(!html.includes('Tindak lanjuti permintaan privasi'));
  assert.ok(!html.includes('Sembunyikan segera'));
});
