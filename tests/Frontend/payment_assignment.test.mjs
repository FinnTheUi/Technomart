import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

function controls(tenantValue = '', stallValue = '') {
    const elements = {};
    for (const [id, value] of [['payment_tenant_id', tenantValue], ['payment_stall_id', stallValue], ['payment-assignment-hint', '']]) {
        elements[id] = { value, textContent: '', listeners: {}, addEventListener(event, handler) { this.listeners[event] = handler; } };
    }
    const assignments = [{ tenant_id: 1, stall_id: 10 }, { tenant_id: 1, stall_id: 11 }, { tenant_id: 2, stall_id: 20 }];
    const view = readFileSync(new URL('../../resources/views/manager/payments.blade.php', import.meta.url), 'utf8');
    const script = view.match(/<script>([\s\S]*?)<\/script>/)[1].replace('@json($tenantStallAssignments)', JSON.stringify(assignments));
    runInNewContext(script, { document: {
        getElementById: id => elements[id],
        addEventListener: (event, handler) => handler(),
    } });
    return { tenant: elements.payment_tenant_id, stall: elements.payment_stall_id, hint: elements['payment-assignment-hint'] };
}

test('selecting a tenant selects their stall and replaces an unrelated selection', () => {
    const { tenant, stall } = controls();
    tenant.value = '2'; tenant.listeners.change();
    assert.equal(stall.value, '20');
    tenant.value = '1'; tenant.listeners.change();
    assert.equal(stall.value, '10');
});

test('selecting any stall selects its tenant', () => {
    const { tenant, stall } = controls();
    stall.value = '11'; stall.listeners.change();
    assert.equal(tenant.value, '1');
    stall.value = '20'; stall.listeners.change();
    assert.equal(tenant.value, '2');
});

test('multiple-stall tenants keep an already matching selection', () => {
    const { tenant, stall, hint } = controls('1', '11');
    tenant.listeners.change();
    assert.equal(stall.value, '11');
    assert.match(hint.textContent, /multiple stalls/);
});

test('clearing either control clears the linked control', () => {
    const { tenant, stall } = controls('2', '20');
    tenant.value = ''; tenant.listeners.change();
    assert.equal(stall.value, '');
    stall.value = '20'; stall.listeners.change();
    stall.value = ''; stall.listeners.change();
    assert.equal(tenant.value, '');
});

test('restored tenant selection initializes its matching stall', () => {
    const { tenant, stall } = controls('2');
    assert.equal(tenant.value, '2');
    assert.equal(stall.value, '20');
});
