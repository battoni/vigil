import type { WalletStatusTone, WalletTypeTone } from './types';

interface WalletTransactionItem {
  id: string;
  amount: string;
  date: string;
  entity: string;
  status: string;
  statusTone: WalletStatusTone;
  type: string;
  typeTone: WalletTypeTone;
}

export type { WalletTransactionItem };
