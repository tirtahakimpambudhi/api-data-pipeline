import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';

type FilterCardProps = {
    title?: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
};

const FilterCard: React.FC<FilterCardProps> = ({ title, description, children, className }) => {
    return (
        <Card className={className}>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                {description && <CardDescription>{description}</CardDescription>}
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
};

export default FilterCard;
