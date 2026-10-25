import assert from 'node:assert/strict';
import { after, test } from 'node:test';
import { mkdtemp, realpath, rm } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { build } from 'esbuild';

const root = fileURLToPath(new URL('../../', import.meta.url));
const parent = await realpath(path.join(root, 'storage/app/private'));
const directory = await mkdtemp(path.join(parent, 'commerce-ui-tests-'));
after(async () => {
  const target = await realpath(directory);
  assert.equal(path.dirname(target), parent);
  assert.ok(path.basename(target).startsWith('commerce-ui-tests-'));
  await rm(target, { recursive: true });
});
const output = path.join(directory, 'components.mjs');
await build({
  stdin: {
    contents: `
      import React from 'react';
      import {renderToStaticMarkup} from 'react-dom/server';
      import {ProductCard} from '@/Components/ProductCard';
      import {App} from '@inertiajs/react';
      import ProductDetail from '@/Pages/Products/ProductDetail';
      ProductDetail.layout = undefined;
      export const detail = product => renderToStaticMarkup(<LanguageProvider><App initialPage={{component:'Products/ProductDetail',url:'/products/'+product.brand+'/'+product.slug,version:null,props:{product,related:[],seo:{title:'Test',description:'Test'},site:{brands:[{key:product.brand,name:product.brand,href:'/products/'+product.brand,tag:{id:'Uji',en:'Test'}}],company:{whatsapp:'6281234567890'}}}}} initialComponent={ProductDetail} resolveComponent={()=>ProductDetail}/></LanguageProvider>);
      import {LanguageProvider} from '@/lib/i18n';
      export {productInquiryUrl} from '@/lib/commerce';
      export const card = (product, showShopLink=true) => renderToStaticMarkup(<LanguageProvider><ProductCard product={product} showShopLink={showShopLink}/></LanguageProvider>);
    `,
    resolveDir: root, sourcefile: 'commerce-card-tests.tsx', loader: 'tsx',
  },
  alias: { '@': path.join(root, 'resources/js') },
  jsx: 'automatic', bundle: true, packages: 'external', platform: 'node', format: 'esm', outfile: output, logLevel: 'silent',
});
const ui = await import(pathToFileURL(output).href);

const official = 'https://shopee.co.id/gpsfood?categoryId=100629&entryPoint=ShopByPDP&itemId=49919300654';
const bilingual = {id:'Produk uji',en:'Test product'};
const fixture = {slug:'uji',brand:'kuicip',name:bilingual,short:bilingual,personality:bilingual,color:'#ffffff',colorDark:'#000000',image:'/images/test.webp',shopeeUrl:official};

test('both catalog brands retain detail links and safe separate Shopee anchors',()=>{
  for (const brand of ['kuicip','putri-teko']) {
    const html=ui.card({...fixture,brand});
    const anchors=[...html.matchAll(/<a\b[^>]*>.*?<\/a>/gs)].map(match=>match[0]);
    assert.equal(anchors.length,2);
    assert.ok(anchors[0].includes('href="/products/'+brand+'/uji"'));
    assert.ok(anchors[1].includes('href="'+official.replaceAll('&','&amp;')+'"'));
    assert.ok(anchors[1].includes('target="_blank"'));
    assert.ok(anchors[1].includes('rel="noopener noreferrer sponsored"'));
    assert.ok(anchors[1].includes('Beli di Shopee'));
    assert.ok(anchors[1].includes('whitespace-nowrap'));
    assert.equal((anchors[0].match(/<a\b/g)||[]).length,1);
    assert.ok(!ui.card({...fixture,brand},false).includes('Beli di Shopee'));
  }
});

test('catalog uses the resolved per-product override and gracefully handles absent destination',()=>{
  assert.ok(ui.card({...fixture,shopeeUrl:'https://shp.ee/override'}).includes('href="https://shp.ee/override"'));
  assert.ok(!ui.card({...fixture,shopeeUrl:null}).includes('Beli di Shopee'));
});

test('WhatsApp retains configured number and product-specific encoded inquiry',()=>{
  for (const brand of ['Kuicip','Putri Teko']) {
    const url=new URL(ui.productInquiryUrl('+62 812-3456-7890',brand,'Rasa & herbal','id'));
    assert.equal(url.origin,'https://wa.me');
    assert.equal(url.pathname,'/6281234567890');
    assert.ok(url.searchParams.get('text').includes(brand+' Rasa & herbal'));
  }
  assert.equal(ui.productInquiryUrl('invalid','Kuicip','Test','id'),null);
});

test('both detail templates render resolved Shopee primary CTA and WhatsApp secondary CTA',()=>{
  for (const brand of ['kuicip','putri-teko']) {
    for (const shopeeUrl of [official,'https://shp.ee/override']) {
      const html=ui.detail({...fixture,brand,shopeeUrl});
      const links=[...html.matchAll(/<a\b[^>]*>.*?<\/a>/gs)].map(match=>match[0]);
      const shop=links.find(link=>link.includes('Beli di Shopee'));
      const whatsapp=links.find(link=>link.includes('Tanya Produk'));
      assert.ok(shop.includes('href="'+shopeeUrl.replaceAll('&','&amp;')+'"'));
      assert.ok(shop.includes('btn-primary'));
      assert.ok(shop.includes('noopener noreferrer'));
      assert.ok(whatsapp.includes('href="https://wa.me/6281234567890?text='));
      assert.ok(whatsapp.includes('btn-outline'));
      assert.ok(whatsapp.includes('noopener noreferrer'));
    }
  }
});
