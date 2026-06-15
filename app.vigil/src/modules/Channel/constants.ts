import type { ChannelType } from './types';
import { CHANNEL_TYPE } from './enums';

interface ChannelTypeOption {
  value: ChannelType;
  labelKey: string;
}

const CHANNEL_TYPE_OPTIONS: ChannelTypeOption[] = [
  { value: CHANNEL_TYPE.SLACK, labelKey: 'channels.type.slack' },
  { value: CHANNEL_TYPE.EMAIL, labelKey: 'channels.type.email' },
  { value: CHANNEL_TYPE.WHATSAPP, labelKey: 'channels.type.whatsapp' },
];

const CHANNEL_TYPE_ICON: Record<ChannelType, string> = {
  [CHANNEL_TYPE.SLACK]: 'pi pi-slack',
  [CHANNEL_TYPE.EMAIL]: 'pi pi-envelope',
  [CHANNEL_TYPE.WHATSAPP]: 'pi pi-whatsapp',
};

export { CHANNEL_TYPE_ICON, CHANNEL_TYPE_OPTIONS };
