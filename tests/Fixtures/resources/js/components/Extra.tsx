export interface ExtraProps {
  label: string;
}

export function Extra(props: ExtraProps) {
  return <span>{props.label}</span>;
}
