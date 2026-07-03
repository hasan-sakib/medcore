import { Medicine } from '@/types';

interface Props {
    medicine: Pick<Medicine, 'reorder_level'> & { stock_on_hand?: number };
}

export default function StockLevelBadge({ medicine }: Props) {
    const stock = medicine.stock_on_hand ?? 0;
    const reorder = medicine.reorder_level;

    let classes = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ';
    let label: string;

    if (stock === 0) {
        classes += 'bg-red-100 text-red-700';
        label = 'Out of stock';
    } else if (stock <= reorder) {
        classes += 'bg-amber-100 text-amber-700';
        label = `Low (${stock})`;
    } else {
        classes += 'bg-green-100 text-green-700';
        label = String(stock);
    }

    return <span className={classes}>{label}</span>;
}
