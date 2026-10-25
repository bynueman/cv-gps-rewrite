import assert from 'node:assert/strict';
import { after, test } from 'node:test';
import { mkdtemp, realpath, rm } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { build } from 'esbuild';

const root = fileURLToPath(new URL('../../', import.meta.url));
const parent = await realpath(path.join(root, 'storage/app/private'));
const directory = await mkdtemp(path.join(parent, 'story-ui-tests-'));
after(async () => {
  const target = await realpath(directory);
  assert.equal(path.dirname(target), parent);
  assert.ok(path.basename(target).startsWith('story-ui-tests-'));
  await rm(target, { recursive: true });
});
const output = path.join(directory, 'components.mjs');
await build({
  stdin: {
    contents: `
      import React from 'react';
      import {renderToStaticMarkup} from 'react-dom/server';
      import {Dialog,DialogTitle,Description} from '@headlessui/react';
      import {StoryCard,StoryActions} from '@/Components/StoryCard';
      import {StoryDetailModal,StoryText} from '@/Components/StoryDetailModal';
      import {LanguageProvider} from '@/lib/i18n';
      export {StoryActions,StoryDetailModal,StoryText,Dialog,DialogTitle,Description};
      export const text = props => renderToStaticMarkup(<StoryText {...props}/>);
      export const cards = stories => renderToStaticMarkup(<LanguageProvider>{stories.map(story => <StoryCard key={story.id} story={story}/>)}</LanguageProvider>);
      export const actions = props => renderToStaticMarkup(<StoryActions {...props}/>);
    `,
    resolveDir: root, sourcefile: 'story-card-tests.tsx', loader: 'tsx',
  },
  alias: { '@': path.join(root, 'resources/js') },
  jsx: 'automatic', bundle: true, packages: 'external', platform: 'node', format: 'esm', outfile: output, logLevel: 'silent',
});
const ui = await import(pathToFileURL(output).href);
const story = {id: 1,code: 'KC-0001',category: 'kehidupan',body: 'Cerita fiksi singkat untuk pengujian.',supports: 3,published_at: '2026-10-26T00:00:00Z'};
function elements(node) {
  if (Array.isArray(node)) return node.flatMap(elements);
  if (!node || typeof node !== 'object' || !node.props) return [];
  return [node, ...elements(node.props.children)];
}

test('preview clamps five lines without truncating short, medium or 1500-character text', () => {
  for (const body of [story.body,'Cerita fiksi. '.repeat(30),'Cerita panjang fiksi. '.repeat(100).slice(0,1500)]) {
    const preview = ui.text({body,preview: true});
    assert.ok(preview.includes('line-clamp-5'));
    assert.ok(preview.includes('h-[7.5rem]'));
    assert.ok(preview.includes(body));
    const full = ui.text({body});
    assert.ok(full.includes(body));
    assert.ok(!full.includes('line-clamp'));
  }
});

test('mixed and consecutive long cards retain bounded previews and separate dialog triggers', () => {
  const rows = [story,{...story,id:2,code:'KC-0002',body:'Panjang '.repeat(185)},{...story,id:3,code:'KC-0003',body:'B'.repeat(1500)}];
  const html = ui.cards(rows);
  assert.equal((html.match(/line-clamp-5/g)||[]).length,3);
  assert.equal((html.match(/aria-haspopup="dialog"/g)||[]).length,3);
  assert.equal((html.match(/aria-expanded="false"/g)||[]).length,3);
  assert.equal((html.match(/Baca selengkapnya<svg/g)||[]).length,3);
  assert.ok(html.includes('[overflow-wrap:anywhere]'));
});

test('user text is escaped and paragraph breaks are preserved in preview and full reading', () => {
  const body = '<img src=x onerror=alert(1)>\n\nParagraf kedua & akhir.';
  for (const preview of [true,false]) {
    const html = ui.text({body,preview});
    assert.ok(html.includes('&lt;img'));
    assert.ok(!html.includes('<img'));
    assert.ok(html.includes('\n\nParagraf kedua &amp; akhir.'));
    assert.ok(html.includes('whitespace-pre-wrap'));
  }
});

test('controlled modal uses the selected full story and installed accessible dialog primitive', () => {
  let closes = 0;
  for (const selected of [story,{...story,code:'KC-0002',body:'Cerita kedua.\nTetap utuh.'}]) {
    const modal = ui.StoryDetailModal({story:selected,open:true,onClose:()=>closes++,lang:'id',children:null});
    assert.equal(modal.type,ui.Dialog);
    assert.equal(modal.props.open,true);
    assert.equal(modal.props.autoFocus,true);
    assert.ok(modal.props.className.includes('motion-reduce:transition-none'));
    const nodes = elements(modal);
    assert.equal(nodes.find(n=>n.type===ui.StoryText).props.body,selected.body);
    assert.ok(nodes.some(n=>n.type===ui.DialogTitle));
    assert.ok(nodes.some(n=>n.type===ui.Description));
    const close = nodes.find(n=>n.type==='button'&&n.props['data-autofocus']);
    assert.equal(close.props['aria-label'],'Tutup cerita');
    close.props.onClick();
    modal.props.onClose(false);
    const reading = nodes.find(n=>n.props.role==='region');
    assert.equal(reading.props.tabIndex,0);
    assert.ok(reading.props.className.includes('overflow-y-auto'));
    assert.ok(nodes.some(n=>n.props.className?.includes('max-h-[85vh]')));
  }
  assert.equal(closes,4);
  assert.equal(ui.StoryDetailModal({story,open:false,onClose:()=>{},lang:'en',children:null}).props.open,false);
});

test('card and modal actions use controlled counts, pending state, and explicit request callbacks', () => {
  let supports = 0;
  let reports = 0;
  const props = {code:story.code,supports:3,busy:false,message:'',lang:'id',onSupport:()=>supports++,onReport:()=>reports++};
  const nodes = elements(ui.StoryActions(props));
  nodes.find(n=>n.type==='button'&&n.props.onClick).props.onClick();
  nodes.find(n=>n.type==='form').props.onSubmit();
  assert.equal(supports,1); assert.equal(reports,1);
  const before = ui.actions(props);
  const after = ui.actions({...props,supports:4,busy:true,message:'Dukungan diterima.'});
  assert.ok(before.includes('</svg>3 '));
  assert.ok(after.includes('</svg>4 '));
  assert.ok(after.includes('disabled=""'));
  assert.ok(after.includes('Dukungan diterima.'));
  assert.equal(supports,1); assert.equal(reports,1);
});
