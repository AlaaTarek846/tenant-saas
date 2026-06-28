export const ACTION_ORDER = ['create', 'read', 'update', 'delete'];

export const CATEGORY_LABELS = {
    user: 'المستخدمون',
    role: 'الأدوار',
    tenant: 'المستأجرون',
    customer: 'العملاء',
    subscription_plan: 'خطط الاشتراك',
    subscription: 'الاشتراكات',
    invoice: 'الفواتير',
    payment: 'المدفوعات',
    account: 'الحسابات',
};

export const ACTION_LABELS = {
    create: 'إنشاء',
    read: 'قراءة',
    update: 'تعديل',
    delete: 'حذف',
};

export function permissionAction(name) {
    return name?.split('_')[0] ?? name;
}

export function permissionCategory(name) {
    return name?.split('_').slice(1).join('_') ?? 'other';
}

export function categoryLabel(category) {
    return CATEGORY_LABELS[category] ?? category;
}

export function actionLabel(name) {
    return ACTION_LABELS[permissionAction(name)] ?? name;
}

export function permissionLabel(name) {
    return `${actionLabel(name)} ${categoryLabel(permissionCategory(name))}`;
}

export function sortByAction(list, nameKey = 'name') {
    return [...list].sort(
        (a, b) => ACTION_ORDER.indexOf(permissionAction(a[nameKey])) - ACTION_ORDER.indexOf(permissionAction(b[nameKey])),
    );
}

export function groupPermissions(permissions) {
    const groups = {};

    for (const permission of permissions) {
        const category = permission.group_category ?? permissionCategory(permission.name);
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(permission);
    }

    return Object.fromEntries(
        Object.entries(groups).map(([category, items]) => [category, sortByAction(items)]),
    );
}

export function toGroupedMultiSelectOptions(permissions) {
    return Object.entries(groupPermissions(permissions)).map(([category, items]) => ({
        label: categoryLabel(category),
        items: items.map((permission) => ({
            label: actionLabel(permission.name),
            value: permission.name,
        })),
    }));
}
