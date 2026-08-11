const store = new Map<string, string>();

const localStorageMock: Storage = {
    get length() {
        return store.size;
    },
    clear(): void {
        store.clear();
    },
    getItem(key: string): string | null {
        return store.has(key) ? store.get(key)! : null;
    },
    key(index: number): string | null {
        return Array.from(store.keys())[index] ?? null;
    },
    removeItem(key: string): void {
        store.delete(key);
    },
    setItem(key: string, value: string): void {
        store.set(key, value);
    },
};

Object.defineProperty(globalThis, 'localStorage', {
    value: localStorageMock,
    configurable: true,
});
